<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Aws\DynamoDb\DynamoDbClient;
use Generator;
use OA\Dynamodb\Metadata\MetadataLoader;
use OA\Dynamodb\Serializer\EntitySerializer;
use Psr\Log\LoggerInterface;
use Throwable;

class EntityManager
{
    private UnitOfWork $unitOfWork;

    public function __construct(
        private readonly DynamoDbClient $dynamoDbClient,
        private readonly MetadataLoader $metadataLoader,
        private readonly EntitySerializer $entitySerializer,
        private readonly OpArgsBuilder $opArgsBuilder,
        private readonly ?LoggerInterface $logger = null,
    )
    {
        $this->unitOfWork = new UnitOfWork($this);
    }

    public function getClient(): DynamoDbClient
    {
        return $this->dynamoDbClient;
    }

    public function getMetadataLoader(): MetadataLoader
    {
        return $this->metadataLoader;
    }

    public function getEntitySerializer(): EntitySerializer
    {
        return $this->entitySerializer;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getOpArgsBuilder(): OpArgsBuilder
    {
        return $this->opArgsBuilder;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    // ---------------------------------------------------------
    // GET — now with IdentityMap support
    // ---------------------------------------------------------

    /**
     * @template T
     * @param class-string<T> $class
     * @param array<string, mixed> $keyFields
     * @return T|null
     * @throws EntityManagerException
     */
    public function getOne(string $class, array $keyFields): ?object
    {
        try {
            // 1. Check IdentityMap first
            $serializedKey = $this->entitySerializer->serializePrimaryKey($class, $keyFields);
            $cached = $this->unitOfWork->getFromIdentityMap($class, $serializedKey);

            $this->logger->debug(__METHOD__, [
                'class' => $class,
                'keyFields' => $keyFields,
                'serializedKey' => $serializedKey,
                'cached' => $cached,
            ]);

            if ($cached !== null) {
                return $cached;
            }

            // 2. Fetch from DynamoDB
            $table = $this->metadataLoader->getEntityMetadata($class)->getTable();
            $result = $this->dynamoDbClient->getItem([
                'TableName' => $table,
                'Key' => $serializedKey,
            ]);

            $item = $result['Item'] ?? null;

            if ($item === null) {
                return null;
            }

            // 3. Deserialize → Register snapshot in IdentityMap
            $entity = $this->entitySerializer->deserialize($item, $class);

            $this->logger->debug(__METHOD__, [
                'entity' => $entity,
                'item' => $item,
            ]);

            $this->unitOfWork->registerManaged($entity);

            return $entity;

        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    // ---------------------------------------------------------
    // BATCH GET (new)
    // ---------------------------------------------------------

    /**
     * @template T
     * @param class-string<T> $class
     * @param array<int, array<string, mixed>> $keyFieldValuesSet
     * @return array<string, T>  // primaryKey → entity
     * @throws EntityManagerException
     */
    public function getMany(string $class, array $keyFieldValuesSet): array
    {
        try {
            $metadata = $this->metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();

            // Prepare batch keys
            $requestKeys = [];
            $result = [];

            foreach ($keyFieldValuesSet as $keyFields) {
                $serializedKey = $this->entitySerializer->serializePrimaryKey($class, $keyFields);
                $cached = $this->unitOfWork->getFromIdentityMap($class, $serializedKey);

                if ($cached !== null) {
                    $result[$this->serializeKeyToString($serializedKey)] = $cached;
                    continue;
                }

                $requestKeys[] = $serializedKey;
            }

            if (empty($requestKeys)) {
                return $result;
            }

            // DynamoDB batchGet (100 keys max per request)
            $chunks = array_chunk($requestKeys, 100);

            foreach ($chunks as $chunk) {
                $response = $this->dynamoDbClient->batchGetItem([
                    'RequestItems' => [
                        $table => [
                            'Keys' => $chunk,
                        ],
                    ],
                ]);

                $items = $response['Responses'][$table] ?? [];

                foreach ($items as $item) {
                    $entity = $this->entitySerializer->deserialize($item, $class);
                    $this->unitOfWork->registerManaged($entity);

                    $serializedKey = $this->entitySerializer->serializePrimaryKey($entity);
                    $result[$this->serializeKeyToString($serializedKey)] = $entity;
                }

                // Retry unprocessed keys
                while (!empty($response['UnprocessedKeys'])) {
                    $response = $this->dynamoDbClient->batchGetItem([
                        'RequestItems' => $response['UnprocessedKeys'],
                    ]);
                }
            }

            return $result;

        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    private function serializeKeyToString(array $key): string
    {
        return json_encode($key, JSON_THROW_ON_ERROR);
    }

    // ---------------------------------------------------------
    // QUERY — now with IdentityMap and snapshots
    // ---------------------------------------------------------

    /**
     * @template T
     * @param class-string<T> $class
     * @param QueryArgs $queryArgs
     * @return object|null
     */
    public function queryOne(string $class, QueryArgs $queryArgs): ?object
    {
        $queryArgs->limit(1);

        /** @var array<int, T> $result */
        $result = $this->query($class, $queryArgs)->getResult(true);

        return $result[0] ?? null;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param QueryArgs $queryArgs
     * @return ResultStream
     */
    public function query(string $class, QueryArgs $queryArgs): ResultStream
    {
        $generator = (function () use ($class, $queryArgs): Generator {
            try {
                $table = $this->metadataLoader->getEntityMetadata($class)->getTable();
                $queryArgs->tableName($table);
                $params = $this->opArgsBuilder->serialize($queryArgs);

                $remainingLimit = $params['Limit'] ?? null;

                do {
                    if ($remainingLimit !== null) {
                        $params['Limit'] = $remainingLimit;
                    }

                    $this->logger?->debug(__METHOD__, [
                        'class' => $class,
                        'params' => $params,
                    ]);

                    $result = $this->dynamoDbClient->query($params);

                    foreach ($result['Items'] ?? [] as $item) {
                        $entity = $this->entitySerializer->deserialize($item, $class);
//                        $serializedKey = $this->entitySerializer->serializePrimaryKey($entity);
//                        $cached = $this->unitOfWork->getFromIdentityMap($class, $serializedKey);

//                        $this->logger->debug(__METHOD__, [
//                            'entity' => $entity,
//                            'serializedKey' => $serializedKey,
//                            'cached' => $cached,
//                        ]);

//                        if ($cached === null) {
                            $this->unitOfWork->registerManaged($entity);
//                        }

                        yield $entity;

                        if ($remainingLimit !== null && --$remainingLimit <= 0) {
                            return;
                        }
                    }

                    $params['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;

                } while (!empty($params['ExclusiveStartKey']));

            } catch (Throwable $exception) {
                $this->wrapException($exception);
            }
        })();

        return new ResultStream($generator);
    }

    // ---------------------------------------------------------
    // SCAN — same IdentityMap rules
    // ---------------------------------------------------------

    public function scan(string $class, ScanArgs $scanArgs): ResultStream
    {
        $generator = (function () use ($class, $scanArgs): Generator {
            try {
                $table = $this->metadataLoader->getEntityMetadata($class)->getTable();
                $scanArgs->tableName($table);
                $params = $this->opArgsBuilder->serialize($scanArgs);
                $remainingLimit = $params['Limit'] ?? null;

                do {
                    if ($remainingLimit !== null) {
                        $params['Limit'] = $remainingLimit;
                    }

                    $result = $this->dynamoDbClient->scan($params);

                    foreach ($result['Items'] ?? [] as $item) {
                        $entity = $this->entitySerializer->deserialize($item, $class);
//                        $serializedKey = $this->entitySerializer->serializePrimaryKey($entity);
//                        $cached = $this->unitOfWork->getFromIdentityMap($class, $serializedKey);

//                        if ($cached === null) {
                            $this->unitOfWork->registerManaged($entity);
//                        }

                        yield $entity;

                        if ($remainingLimit !== null && --$remainingLimit <= 0) {
                            return;
                        }
                    }

                    $params['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;

                } while (!empty($params['ExclusiveStartKey']));
            } catch (Throwable $exception) {
                $this->wrapException($exception);
            }
        })();

        return new ResultStream($generator);
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param UpdateArgs $updateArgs
     * @param array $keyFieldValues
     * @return object|null
     * @throws EntityManagerException
     */
    public function updateOneByQueryReturn(string $class, UpdateArgs $updateArgs, array $keyFieldValues): ?object
    {
        try {
            $key = $this->entitySerializer->serializePrimaryKey($class, $keyFieldValues);
            $updateArgs->key($key);

            $table = $this->metadataLoader->getEntityMetadata($class)->getTable();
            $updateArgs->tableName($table)->returnValues('ALL_NEW');

            $params = $this->opArgsBuilder->serialize($updateArgs);

            $result = $this->dynamoDbClient->updateItem($params);

            $item = $result['Attributes'] ?? null;
            if (null === $item) {
                return null;
            }

            $entity = $this->entitySerializer->deserialize($item, $class);
//            $serializedKey = $this->entitySerializer->serializePrimaryKey($entity);
//            $cached = $this->unitOfWork->getFromIdentityMap($class, $serializedKey);

//            if ($cached === null) {
                $this->unitOfWork->registerManaged($entity);
//            }

            return $entity;
        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    // ---------------------------------------------------------
    // WRITE OPERATIONS
    // ---------------------------------------------------------

    public function persist(object $entity): void
    {
        $this->unitOfWork->scheduleForInsert($entity);
    }

    public function remove(object $entity): void
    {
        $this->unitOfWork->scheduleForDelete($entity);
    }

    public function flush(): void
    {
        $this->unitOfWork->flush();
    }

    // ---------------------------------------------------------
    // Exception wrapper
    // ---------------------------------------------------------

    private function wrapException(Throwable $exception): never
    {
        $this->logger?->error($exception);
        throw new EntityManagerException(
            sprintf('An error occurred. %s: %s', $exception::class, $exception->getMessage())
        );
    }
}