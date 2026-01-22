<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTerm;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<SearchTerm>
 */
class SearchTermDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, SearchTerm::class);
    }

    public function find(string $id): ?SearchTerm
    {
        return $this->getOne(['id' => $id]);
    }

    public function findByIds(array $ids): array
    {
        return $this->getMany(array_map(static fn (string $id): array => ['id' => $id], $ids));
    }
}
