<?php

declare(strict_types=1);

namespace App\Service\ORM;

use App\Model\Repository\EntityRepositoryConfig;
use Doctrine\ORM\EntityManagerInterface as DoctrineEntityManager;
use OA\Dynamodb\ODM\EntityManager as DynamodbEntityManager;

/**
 * @method void persist(object $object)
 * @method void flush()
 * @method void remove(object $object)
 */
class EntityManager
{
    public function __construct(
        private EntityRepositoryConfig $config,
        private DoctrineEntityManager $doctrine,
        private DynamodbEntityManager $dynamodb,
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

    public function getDoctrine(): DoctrineEntityManager
    {
        return $this->doctrine;
    }

    public function getDynamodb(): DynamodbEntityManager
    {
        return $this->dynamodb;
    }

    public function getConfig(): EntityRepositoryConfig
    {
        return $this->config;
    }
}