<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotRequest;
use App\Model\Telegram\TelegramBotRequestLimits;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBotRequest>
 */
class TelegramBotRequestDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBotRequest::class);
    }

    public function getLimits(null|int|string $chatId): ?TelegramBotRequestLimits
    {
        $now = new DateTime();
        $oneSecondAgo = (clone $now)->modify('-1 second');
        $oneMinuteAgo = (clone $now)->modify('-1 minute');

        // Count distinct chats in the last second
        $perSecondAll = $this->createQueryBuilder('tr')
            ->select('COUNT(DISTINCT tr.chatId)')
            ->andWhere('tr.createdAt >= :oneSecondAgo')
            ->setParameter('oneSecondAgo', $oneSecondAgo)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($perSecondAll === null) {
            return null;
        }

        // Count messages in this chat in the last second
        $perSecond = $this->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->andWhere('tr.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->andWhere('tr.createdAt >= :oneSecondAgo')
            ->setParameter('oneSecondAgo', $oneSecondAgo)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // Count messages in this chat in the last minute
        $perMinute = $this->createQueryBuilder('tr')
            ->select('COUNT(tr.id)')
            ->andWhere('tr.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->andWhere('tr.createdAt >= :oneMinuteAgo')
            ->setParameter('oneMinuteAgo', $oneMinuteAgo)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return new TelegramBotRequestLimits(
            (int) $perSecondAll,
            (int) $perSecond,
            (int) $perMinute
        );
    }
}
