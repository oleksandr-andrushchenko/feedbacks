<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\User\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function findOneLast(): ?Feedback
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }

    public function countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', $from)
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->andWhere('f.hasActiveSubscription = :hasActiveSubscription')
            ->setParameter('hasActiveSubscription', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param string $normalizedText
     * @param bool $withUsers
     * @param int $maxResults
     * @return Feedback[]
     */
    public function findByNormalizedText(string $normalizedText, bool $withUsers = false, int $maxResults = 100): array
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->addSelect('mu')
            ->innerJoin('f.searchTerms', 't')
            ->innerJoin('f.messengerUser', 'mu')
        ;

        if ($withUsers) {
            $queryBuilder
                ->addSelect('u')
                ->innerJoin('f.user', 'u')
            ;
        }

        return $queryBuilder
            ->andWhere('t.normalizedText = :normalizedText')
            ->setParameter('normalizedText', $normalizedText)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return Paginator|Feedback[]
     */
    public function findUnpublishedByPeriod(DateTimeInterface $from, DateTimeInterface $to): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('f');

        $query = $queryBuilder
            ->addSelect('u', 'mu', 'tb', 'st')
            ->innerJoin('f.user', 'u')
            ->innerJoin('f.messengerUser', 'mu')
            ->innerJoin('f.telegramBot', 'tb')
            ->innerJoin('f.searchTerms', 'st')
            ->andWhere(
                $queryBuilder->expr()->isNull('f.channelMessageIds')
            )
            ->andWhere(
                $queryBuilder->expr()->gte('f.createdAt', ':createdAtFrom'),
                $queryBuilder->expr()->lt('f.createdAt', ':createdAtTo'),
            )
            ->setParameter('createdAtFrom', $from, Types::DATE_IMMUTABLE)
            ->setParameter('createdAtTo', $to, Types::DATE_IMMUTABLE)
        ;

        return new Paginator($query);
    }
}
