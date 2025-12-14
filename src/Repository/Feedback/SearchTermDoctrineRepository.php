<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SearchTerm>
 */
class SearchTermDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchTerm::class);
    }

    public function findByIds(array $ids): array
    {
        return $this->findBy([
            'id' => $ids,
        ]);
    }

    public function findOneByNormalizedTextTypeText(string $normalizedText, SearchTermType $type, string $text): ?SearchTerm
    {
        return $this->findOneBy([
            'normalizedText' => $normalizedText,
            'type' => $type,
            'text' => $text,
        ]);
    }

    public function findByPeriod(DateTimeInterface $from, DateTimeInterface $to): iterable
    {
        $queryBuilder = $this->createQueryBuilder('fst');

        $query = $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->gte('fst.createdAt', ':createdAtFrom'),
                $queryBuilder->expr()->lt('fst.createdAt', ':createdAtTo'),
            )
            ->setParameter('createdAtFrom', $from, Types::DATE_IMMUTABLE)
            ->setParameter('createdAtTo', $to, Types::DATE_IMMUTABLE)
        ;

        return new Paginator($query);
    }
}
