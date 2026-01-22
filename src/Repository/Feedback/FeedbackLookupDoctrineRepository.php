<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\User\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackLookup>
 * @method array<FeedbackLookup> findAll()
 */
class FeedbackLookupDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackLookup::class);
    }

    /**
     * @return array<FeedbackLookup>
     */
    public function findAllWithSearchTerms(): array
    {
        return $this->createQueryBuilder('fl')
            ->addSelect('searchTerm')
            ->join('fl.searchTerm', 'searchTerm')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneLast(): ?FeedbackLookup
    {
        return $this->findOneBy([], ['createdAt' => 'DESC']);
    }

    /**
     * @param string $normalizeText
     * @param int $maxResults
     * @return FeedbackLookup[]
     */
    public function findByNormalizedText(string $normalizeText, int $maxResults = 100): array
    {
        return $this->createQueryBuilder('fl')
            ->addSelect('mu')
            ->innerJoin('fl.searchTerm', 't')
            ->innerJoin('fl.messengerUser', 'mu')
            ->andWhere('t.normalizedText = :normalizedText')
            ->setParameter('normalizedText', $normalizeText)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from): int
    {
        return (int) $this->createQueryBuilder('fl')
            ->select('COUNT(fl.id)')
            ->andWhere('fl.createdAt >= :createdAtFrom')
            ->setParameter('createdAtFrom', $from)
            ->andWhere('fl.user = :user')
            ->setParameter('user', $user)
            ->andWhere('fl.hasActiveSubscription = :hasActiveSubscription')
            ->setParameter('hasActiveSubscription', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
