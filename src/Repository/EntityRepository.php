<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Repository\EntityRepositoryConfig;
use OA\Dynamodb\ODM\EntityRepository as DynamodbEntityRepository;

/**
 * @template T of object
 */
class EntityRepository
{
    public function __construct(
        protected EntityRepositoryConfig $config,
        protected DynamodbEntityRepository $dynamodb,
    )
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this->dynamodb, $name], $arguments);
    }

    public function getConfig(): EntityRepositoryConfig
    {
        return $this->config;
    }

    public function getDynamodb(): DynamodbEntityRepository
    {
        return $this->dynamodb;
    }
}
