<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramBotGroupName;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<TelegramBot>
 */
class TelegramBotDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBot::class);
    }

    public function findAll(): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT',
                ])
        );
    }

    public function findOneByUsername(string $username): ?TelegramBot
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_USERNAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_username_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $username,
                ])
        );
    }

    public function findOneNonDeletedByUsername(string $username): ?TelegramBot
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_USERNAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_username_pk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => $username,
                ])
        );
    }

    public function findNonDeletedByGroup(TelegramBotGroupName $group): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_pk',
                    '#sk' => 'telegram_bot_group_country_locale_sk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT',
                    ':sk' => $group->name . '#',
                ])
        );
    }

    public function findPrimaryNonDeletedByGroup(TelegramBotGroupName $group): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->filterExpression([
                    'attribute_exists(#primary)',
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_pk',
                    '#sk' => 'telegram_bot_group_country_locale_sk',
                    '#primary' => 'primary',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT',
                    ':sk' => $group->name . '#',
                ])
        );
    }

    public function findNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_pk',
                    '#sk' => 'telegram_bot_group_country_locale_sk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT',
                    ':sk' => $group->name . '#' . $countryCode . '#',
                ])
        );
    }

    public function findOnePrimaryNonDeletedByBot(TelegramBot $bot): ?TelegramBot
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE')
                ->keyConditionExpression([
                    '#pk = :pk',
                    '#sk = :sk',
                ])
                ->filterExpression([
                    'attribute_exists(#primary)',
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_pk',
                    '#sk' => 'telegram_bot_group_country_locale_sk',
                    '#primary' => 'primary',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT',
                    ':sk' => $bot->getGroup()->name . '#' . $bot->getCountryCode() . '#' . $bot->getLocaleCode(),
                ])
        );
    }

    public function findPrimaryNonDeletedByGroupAndIds(TelegramBotGroupName $group, array $ids): array
    {
        return array_filter(
            $this->findPrimaryNonDeletedByGroup($group),
            static fn (TelegramBot $bot): bool => in_array($bot->getId(), $ids, true)
        );
    }
}
