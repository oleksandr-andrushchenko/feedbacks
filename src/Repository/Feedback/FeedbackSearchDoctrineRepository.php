<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\User\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackSearch>
 */
class FeedbackSearchDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackSearch::class);
    }

    public function findOneLast(): ?FeedbackSearch
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }

    public function countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from): int
    {
        return (int) $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->andWhere('fs.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', $from)
            ->andWhere('fs.user = :user')
            ->setParameter('user', $user)
            ->andWhere('fs.hasActiveSubscription = :hasActiveSubscription')
            ->setParameter('hasActiveSubscription', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param string $normalizeText
     * @param bool $withUsers
     * @param int $maxResults
     * @return FeedbackSearch[]
     */
    public function findByNormalizedText(string $normalizeText, bool $withUsers = false, int $maxResults = 100): array
    {
        $queryBuilder = $this->createQueryBuilder('fs')
            ->addSelect('mu')
            ->innerJoin('fs.searchTerm', 't')
            ->innerJoin('fs.messengerUser', 'mu')
        ;

        if ($withUsers) {
            $queryBuilder
                ->addSelect('u')
                ->innerJoin('fs.user', 'u')
            ;
        }

        return $queryBuilder
            ->andWhere('t.normalizedText = :normalizedText')
            ->setParameter('normalizedText', $normalizeText)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }
}
