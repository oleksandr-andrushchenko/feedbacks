<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Repository\EntityRepositoryConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as DoctrineEntityRepository;
use OA\Dynamodb\ODM\EntityRepository as DynamodbEntityRepository;

/**
 * @template T of object
 */
class EntityRepository
{
    public function __construct(
        protected EntityRepositoryConfig $config,
        protected ?DoctrineEntityRepository $doctrine = null,
        protected ?DynamodbEntityRepository $dynamodb = null,
    )
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($this->config->isDynamodb()) {
            return call_user_func_array([$this->dynamodb, $name], $arguments);
        }

        return call_user_func_array([$this->doctrine, $name], $arguments);
    }

    public function getConfig(): EntityRepositoryConfig
    {
        return $this->config;
    }

    public function getDoctrine(): ?DoctrineEntityRepository
    {
        return $this->doctrine;
    }

    public function getDynamodb(): ?DynamodbEntityRepository
    {
        return $this->dynamodb;
    }
}