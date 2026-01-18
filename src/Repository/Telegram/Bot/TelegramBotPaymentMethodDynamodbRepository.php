<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<TelegramBotPaymentMethod>
 */
class TelegramBotPaymentMethodDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotPaymentMethod::class);
    }

    public function find(string $id): ?TelegramBotPaymentMethod
    {
        return $this->getOne(['id' => $id]);
    }

    public function findActiveByBot(TelegramBot $bot): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('TELEGRAM_BOT_PAYMENT_METHODS_BY_TELEGRAM_BOT')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'telegram_bot_payment_method_telegram_bot_id_pk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => $bot->getId(),
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
        );
    }
}
