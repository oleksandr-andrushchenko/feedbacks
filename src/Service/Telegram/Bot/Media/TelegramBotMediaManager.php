<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Media;

use App\Service\Telegram\Bot\TelegramBot;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Longman\TelegramBot\Entities\PhotoSize;

class TelegramBotMediaManager
{
    private DynamoDbClient $ddb;
    private string $tableName;
    private int $albumTimeout; // seconds

    public function __construct(DynamoDbClient $ddb, string $tableName = 'telegram_albums', int $albumTimeout = 2)
    {
        $this->ddb = $ddb;
        $this->tableName = $tableName;
        $this->albumTimeout = $albumTimeout;
    }

    /**
     * @param array<PhotoSize> $photoSizes
     */
    public function acceptMedia(TelegramBot $bot, string $mediaGroupId, array $photoSizes): void
    {
        $photo = end($photoSizes);
        if (!$photo) {
            return;
        }
        if (!$photo->getFileId()) {
            return;
        }

        // todo: TODO!!!!

        $fileId = $photo->getFileId();
        $userId = $message['from']['id'] ?? null;

        // single photo
        if ($mediaGroupId === null) {
            $this->processAlbum([$fileId]);
            return;
        }

        // generate a logical “album key” combining user + recent time
        // use a timestamp window to combine multiple groups into one logical album
        $timeWindow = (int) (time() / $this->albumTimeout);
        $albumKey = "album_{$userId}_{$timeWindow}";

        // store photo for this media group
        try {
            $this->ddb->updateItem([
                'TableName' => $this->tableName,
                'Key' => ['pk' => ['S' => $mediaGroupId]],
                'UpdateExpression' => 'SET photos = list_append(if_not_exists(photos, :empty), :file), created_at = :now, expire_at = :ttl',
                'ExpressionAttributeValues' => [
                    ':empty' => ['L' => []],
                    ':file' => ['L' => [['S' => $fileId]]],
                    ':now' => ['N' => (string) time()],
                    ':ttl' => ['N' => (string) (time() + 120)],
                ],
            ]);
        } catch (DynamoDbException $e) {
            error_log($e->getMessage());
        }

        // try to acquire processing lock for the **logical album**
        try {
            $this->ddb->updateItem([
                'TableName' => $this->tableName,
                'Key' => ['pk' => ['S' => $albumKey]],
                'UpdateExpression' => 'SET processing = :true',
                'ConditionExpression' => 'attribute_not_exists(processing)',
                'ExpressionAttributeValues' => [
                    ':true' => ['BOOL' => true],
                ],
            ]);
            $lock = true;
        } catch (DynamoDbException $e) {
            $lock = false;
        }


        if ($lock) {
            // wait for all groups to arrive
            usleep((int) ($this->albumTimeout * 1_000_000));

            // collect all photos from groups
            try {
                // scan all group items created within the album TTL window
                $res = $this->ddb->scan([
                    'TableName' => $this->tableName,
                    'FilterExpression' => 'begins_with(pk, :prefix)',
                    'ExpressionAttributeValues' => [
                        ':prefix' => ['S' => $albumKey],
                    ],
                ]);

                $allPhotos = [];
                foreach ($res['Items'] as $item) {
                    if (isset($item['photos']['L'])) {
                        foreach ($item['photos']['L'] as $p) {
                            $allPhotos[] = (string) ($p['S'] ?? '');
                        }
                    }
                }
            } catch (DynamoDbException $e) {
                $allPhotos = [];
            }

            if ($allPhotos !== []) {
                $this->processAlbum($allPhotos);
            }

            // clear album by user key
            try {
                $res = $this->ddb->scan([
                    'TableName' => $this->tableName,
                    'FilterExpression' => 'begins_with(pk, :prefix)',
                    'ExpressionAttributeValues' => [
                        ':prefix' => ['S' => $albumKey],
                    ],
                ]);

                foreach ($res['Items'] as $item) {
                    $pk = $item['pk']['S'] ?? null;
                    if ($pk) {
                        $this->ddb->deleteItem([
                            'TableName' => $this->tableName,
                            'Key' => ['pk' => ['S' => $pk]],
                        ]);
                    }
                }
            } catch (DynamoDbException $e) {
                error_log($e->getMessage());
            }
        }
    }

    /**
     * @param array<int, string> $photos
     */
    private function processAlbum(array $photos): void
    {
        foreach ($photos as $fileId) {
            echo "Processing photo: $fileId\n";
        }
    }
}