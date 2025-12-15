<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityManager<TelegramBotPaymentMethod>
 */
class TelegramBotPaymentMethodDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotPaymentMethod::class);
    }

    /**
     * @param TelegramBot $bot
     * @return TelegramBotPaymentMethod[]
     */
    public function findActiveByBot(TelegramBot $bot): array
    {
        return $this->findBy([
            'bot' => $bot,
            'deletedAt' => null,
        ]);
    }

    public function findOneActiveByBotAndName(TelegramBot $bot, TelegramBotPaymentMethodName $name): ?TelegramBotPaymentMethod
    {
        return $this->findOneBy([
            'bot' => $bot,
            'name' => $name,
            'deletedAt' => null,
        ]);
    }
}
