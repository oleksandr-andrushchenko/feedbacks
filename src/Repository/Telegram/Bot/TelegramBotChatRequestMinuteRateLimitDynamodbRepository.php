<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotChatRequestMinuteRateLimit;
use DateTimeImmutable;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\UpdateArgs;

/**
 * @extends EntityRepository<TelegramBotChatRequestMinuteRateLimit>
 */
class TelegramBotChatRequestMinuteRateLimitDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotChatRequestMinuteRateLimit::class);
    }

    public function incrementCountByChatAndMinute(int $chatId, int $minute): TelegramBotChatRequestMinuteRateLimit
    {
        $args = (new UpdateArgs())
            ->updateExpression('
                SET #chatId = if_not_exists(#chatId, :chatId),
                #minute = if_not_exists(#minute, :minute),
                #expireAt = if_not_exists(#expireAt, :expireAt)
                ADD #count :countInc
            ')
            ->expressionAttributeNames([
                '#chatId' => 'chat_id',
                '#minute' => 'minute',
                '#count' => 'count',
                '#expireAt' => 'expire_at',
            ])
            ->expressionAttributeValues([
                ':chatId' => $chatId,
                ':minute' => $minute,
                ':countInc' => 1,
                ':expireAt' => (new DateTimeImmutable())->setTimestamp(time() + 62)->format('c'),
            ])
        ;

        return $this->updateOneByQueryReturn($args, [
            'chatId' => $chatId,
            'minute' => $minute,
        ]);
    }
}
