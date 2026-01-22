<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\User;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityManagerException;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<User>
 */
class UserDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, User::class);
    }

    public function find(string $id): ?User
    {
        return $this->getOne(['id' => $id]);
    }

    /**
     * @param array $ids
     * @return array<User>
     * @throws EntityManagerException
     */
    public function findByIds(array $ids): array
    {
        return $this->getMany(array_map(static fn (string $id): array => ['id' => $id], $ids));
    }
}
