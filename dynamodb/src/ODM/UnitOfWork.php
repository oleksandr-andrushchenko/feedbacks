<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Throwable;

class UnitOfWork
{
    /**
     * @var array<
     *   class-string,
     *   array<string, object> // [serializedPk => entity]
     * >
     */
    private array $identities = [];

    /** @var array<string, array> */
    private array $originals = []; // [oid => serialized attributes snapshot]

    /** @var array<string, object> */
    private array $inserts = [];

    /** @var array<string, object> */
    private array $deletes = [];

    /** @var array<string, object> */
    private array $updates = [];

    public function __construct(
        private readonly EntityManager $em,
    )
    {
    }

    public function register(object $entity): object
    {
        $class = $entity::class;
        $idKey = $this->getIdentityKey($entity);

        if (isset($this->identities[$class][$idKey])) {
            return $this->identities[$class][$idKey];
        }

        $this->identities[$class][$idKey] = $entity;
        $this->originals[$idKey] = $this->em->getEntitySerializer()->serialize($entity);

        return $entity;
    }

    public function scheduleForInsert(object $entity): void
    {
        $entity = $this->register($entity);
        $idKey = $this->getIdentityKey($entity);
        $this->inserts[$idKey] = $entity;
    }

    public function scheduleForDelete(object $entity): void
    {
        $entity = $this->register($entity);
        $idKey = $this->getIdentityKey($entity);
        $this->deletes[$idKey] = $entity;
    }

    public function flush(): void
    {
        $client = $this->em->getClient();
        $metadataLoader = $this->em->getMetadataLoader();
        $serializer = $this->em->getEntitySerializer();
        $logger = $this->em->getLogger();

        // ----------------------------
        // AUTOMATIC DIRTY DETECTION
        // ----------------------------
        foreach ($this->identities as $class => $entities) {
            foreach ($entities as $idKey => $entity) {
                if (isset($this->inserts[$idKey]) || isset($this->deletes[$idKey])) {
                    continue;
                }

                $current = $serializer->serialize($entity);
                $original = $this->originals[$idKey] ?? null;

                $logger?->debug(__METHOD__, [
                    'class' => $class,
                    'id' => $idKey,
                    'entity' => $entity,
                    'original' => $original,
                    'current' => $current,
                    'updated' => $current !== $original,
                ]);

                if ($current !== $original) {
                    $this->updates[$idKey] = $entity;
                }
            }
        }

        $writes = [];

        // DELETES
        foreach ($this->deletes as $idKey => $entity) {
            $class = $entity::class;
            $metadata = $metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();
            $key = $serializer->serializePrimaryKey($entity);

            $writes[] = [
                'Delete' => [
                    'TableName' => $table,
                    'Key' => $key,
                ],
            ];

            unset($this->inserts[$idKey], $this->updates[$idKey]);
        }

        // INSERTS
        foreach ($this->inserts as $idKey => $entity) {
            $class = $entity::class;
            $metadata = $metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();
            $item = $serializer->serialize($entity);

            $logger->debug(__METHOD__ . ':insert', $item);

            $writes[] = [
                'Put' => [
                    'TableName' => $table,
                    'Item' => $item,
                    'ConditionExpression' => 'attribute_not_exists(#pk)',
                    'ExpressionAttributeNames' => [
                        '#pk' => $metadata->getPartitionKey()->getName(),
                    ],
                ],
            ];
        }

        // UPDATES (dirty)
        foreach ($this->updates as $idKey => $entity) {
            $class = $entity::class;
            $metadata = $metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();

            $newData = $serializer->serialize($entity);
            $oldData = $this->originals[$idKey];

            // optimistic lock?
            $hasVersion = $metadata->hasProperty('version');
            $condition = null;
            $condNames = null;
            $condValues = null;

            if ($hasVersion) {
                $versionField = $metadata->getProperty('version')->getName() ?: 'version';
                $oldVersion = $oldData[$versionField]['N'] ?? null;

                $condition = '#v = :v';
                $condNames = ['#v' => $versionField];
                $condValues = [':v' => ['N' => $oldVersion]];
            }

            $logger->debug(__METHOD__ . ':update', $newData);

            $writes[] = [
                'Put' => [
                    'TableName' => $table,
                    'Item' => $newData,
                    'ConditionExpression' => $condition ?? null,
                    'ExpressionAttributeNames' => $condNames ?? [],
                    'ExpressionAttributeValues' => $condValues ?? [],
                ],
            ];
        }

        $logger->debug(__METHOD__, [
            'writes_count' => count($writes),
            'writes' => $writes,
        ]);

        if (count($writes) === 0) {
            $this->afterFlush();
            return;
        }

        // -------------------------------------------------
        // TRANSACTION (<= 25 ops)
        // -------------------------------------------------
        if (count($writes) <= 25) {
            $logger?->debug('UnitOfWork::flush using TransactWriteItems', ['writes' => $writes]);

            try {
                $client->transactWriteItems(['TransactItems' => $writes]);
            } catch (Throwable $exception) {
                $this->em->getLogger()->error($exception->getMessage(), [
                    'writes' => $writes,
                ]);
                throw $exception;
            }
            $this->afterFlush();
            return;
        }

        // -------------------------------------------------
        // FALLBACK → batchWriteItem() (100 writes per batch)
        // -------------------------------------------------

        $logger?->debug('UnitOfWork::flush using batchWriteItem', ['count' => count($writes)]);

        $batched = [];
        foreach ($writes as $write) {
            $opType = array_key_first($write); // Put | Delete
            $table = $write[$opType]['TableName'];

            $batched[$table][] = $write;
        }

        foreach ($batched as $table => $ops) {
            $chunks = array_chunk($ops, 25);

            foreach ($chunks as $chunk) {
                $items = [];

                foreach ($chunk as $op) {
                    if (isset($op['Put'])) {
                        $items[] = ['PutRequest' => ['Item' => $op['Put']['Item']]];
                    } elseif (isset($op['Delete'])) {
                        $items[] = ['DeleteRequest' => ['Key' => $op['Delete']['Key']]];
                    }
                }

                $logger->debug(__METHOD__, [
                    'batchWriteItem' => ['RequestItems' => [$table => $items]],
                ]);

                $client->batchWriteItem(['RequestItems' => [$table => $items]]);
            }
        }

        $this->afterFlush();
    }

    private function afterFlush(): void
    {
        $this->inserts = [];
        $this->deletes = [];
        $this->updates = [];
    }

    public function clear(): void
    {
        $this->identities = [];
        $this->originals = [];
        $this->inserts = [];
        $this->deletes = [];
        $this->updates = [];
    }

    public function getById(string $class, array $key): ?object
    {
        $idKey = $this->getIdentityKeyByArray($key);

        return $this->identities[$class][$idKey] ?? null;
    }

    public function getIdentityKeyByArray(array $key): string
    {
        return json_encode($key, JSON_THROW_ON_ERROR);
    }

    public function getIdentityKey(object $entity): string
    {
        return $this->getIdentityKeyByArray($this->em->getEntitySerializer()->serializePrimaryKey($entity));
    }
}