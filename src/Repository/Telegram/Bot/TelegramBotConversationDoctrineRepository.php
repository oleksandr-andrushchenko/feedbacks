<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotConversation>
 */
class TelegramBotConversationDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotConversation::class);
    }

    public function findOneNonDeletedByHash(string $hash): ?TelegramBotConversation
    {
        return $this->findOneBy([
            'hash' => $hash,
            'deletedAt' => null,
        ]);
    }
}
