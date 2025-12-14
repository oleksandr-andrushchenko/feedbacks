<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\User\User;
use DateTimeInterface;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<FeedbackLookup>
 */
class FeedbackLookupDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, FeedbackLookup::class);
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
