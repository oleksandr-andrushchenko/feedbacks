<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Psr\Log\LoggerInterface;

/**
 * @template T of object
 */
class EntityRepository
{
    /**
     * @param EntityManager $em
     * @param class-string<T> $entityClass The class name of the entity this repository manages
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        protected EntityManager $em,
        protected string $entityClass,
        protected ?LoggerInterface $logger = null,
    )
    {
    }

    /**
     * @param array $keyFieldValues
     * @return T|null
     * @throws EntityManagerException
     */
    public function getOne(array $keyFieldValues): ?object
    {
        return $this->em->getOne($this->entityClass, $keyFieldValues);
    }

    /**
     * @param array<array> $keyFieldValuesSet
     * @return array<T>
     * @throws EntityManagerException
     */
    public function getMany(array $keyFieldValuesSet): array
    {
        return $this->em->getMany($this->entityClass, $keyFieldValuesSet);
    }

    /**
     * @param QueryArgs $queryArgs
     * @return T|null
     */
    public function queryOne(QueryArgs $queryArgs): ?object
    {
        return $this->em->queryOne($this->entityClass, $queryArgs);
    }

    /**
     * @param QueryArgs $queryArgs
     * @return array<T>
     */
    public function queryMany(QueryArgs $queryArgs): array
    {
        return $this->em->query($this->entityClass, $queryArgs)->getResult(true);
    }

    public function updateOneByQueryReturn(UpdateArgs $updateArgs, array $keyFieldValues): ?object
    {
        return $this->em->updateOneByQueryReturn($this->entityClass, $updateArgs, $keyFieldValues);
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
