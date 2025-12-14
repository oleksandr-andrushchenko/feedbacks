<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotPaymentMethod>
 */
class TelegramBotPaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotPaymentMethod::class);
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
