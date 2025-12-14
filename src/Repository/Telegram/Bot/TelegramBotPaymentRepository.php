<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotPayment>
 */
class TelegramBotPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotPayment::class);
    }

    public function findOneByUuid(string $uuid): ?TelegramBotPayment
    {
        return $this->findOneBy([
            'uuid' => $uuid,
        ]);
    }
}
