<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Enum\Telegram\TelegramBotGroupName;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<TelegramChannel>
 */
class TelegramChannelDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramChannel::class);
    }

    public function findAll(): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_CHANNEL',
                ])
        );
    }

    public function findOneByUsername(string $username): ?TelegramChannel
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_USERNAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_username_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $username,
                ])
        );
    }

    public function findOneNonDeletedByUsername(string $username): ?TelegramChannel
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_USERNAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_username_pk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => $username,
                ])
        );
    }

    public function findOnePrimaryNonDeletedByBot(TelegramBot $bot): ?TelegramChannel
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    '#sk = :sk',
                ])
                ->filterExpression([
                    'attribute_exists(#primary)',
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_pk',
                    '#sk' => 'telegram_channel_group_country_locale_sk',
                    '#primary' => 'primary',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_CHANNEL',
                    ':sk' => $bot->getGroup()->value . '#' . $bot->getCountryCode() . '#' . $bot->getLocaleCode(),
                ])
        );
    }

    public function findOnePrimaryNonDeletedByChannel(TelegramChannel $channel): ?TelegramChannel
    {
        $filters = [
            'attribute_exists(#primary)',
            'attribute_not_exists(#deletedAt)',
        ];

        $values = [
            ':pk' => 'TELEGRAM_CHANNEL',
            ':sk' => $channel->getGroup()->value . '#' . $channel->getCountryCode() . '#' . $channel->getLocaleCode(),
        ];

        if ($channel->getLevel1RegionId() == null) {
            $filters[] = 'attribute_not_exists(#level1RegionId)';
        } else {
            $filters[] = '#level1RegionId = :level1RegionId';
            $values[':level1RegionId'] = $channel->getLevel1RegionId();
        }

        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    '#sk = :sk',
                ])
                ->filterExpression($filters)
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_pk',
                    '#sk' => 'telegram_channel_group_country_locale_sk',
                    '#primary' => 'primary',
                    '#level1RegionId' => 'level_1_region_id',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues($values)
        );
    }

    public function findPrimaryNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_CHANNELS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->filterExpression([
                    'attribute_exists(#primary)',
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_channel_pk',
                    '#sk' => 'telegram_channel_group_country_locale_sk',
                    '#primary' => 'primary',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_CHANNEL',
                    ':sk' => $group->value . '#' . $countryCode . '#',
                ])
        );
    }
}
