<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Throwable;

class UnitOfWork
{
    /** @var array<string, object> */
    private array $identityMap = []; // [oid => entity]

    /** @var array<string, array> */
    private array $originalData = []; // [oid => serialized attributes snapshot]

    /** @var array<string, object> */
    private array $newEntities = [];

    /** @var array<string, object> */
    private array $removedEntities = [];

    /** @var array<string, object> */
    private array $dirtyEntities = [];

    public function __construct(
        private readonly EntityManager $em,
    )
    {
    }

    // -------------------------------------------------
    // Identity Map + Snapshot
    // -------------------------------------------------

    public function registerManaged(object $entity): void
    {
        $oid = spl_object_hash($entity);

        if (isset($this->identityMap[$oid])) {
            return;
        }

        // todo: do I really need this?
        $serializedKey = $this->em->getEntitySerializer()->serializePrimaryKey($entity);
        $cached = $this->getFromIdentityMap($entity::class, $serializedKey);

        if ($cached !== null) {
            return;
        }

        $this->identityMap[$oid] = $entity;
        $this->originalData[$oid] = $this->em->getEntitySerializer()->serialize($entity);

        $this->em->getLogger()->debug(__METHOD__, [
            'oid' => $oid,
            'entity' => $entity,
            'serializedItem' => $this->originalData[$oid],
        ]);
    }

    public function getFromIdentityMap(object|string $class, array $key): ?object
    {
        foreach ($this->identityMap as $entity) {
            if ($entity::class !== (is_string($class) ? $class : $entity::class)) {
                continue;
            }

            $currentKey = $this->em->getEntitySerializer()->serializePrimaryKey($entity);

            if ($currentKey == $key) {
                return $entity;
            }
        }

        return null;
    }

    // -------------------------------------------------
    // Schedulers (insert/delete/dirty)
    // -------------------------------------------------

    public function scheduleForInsert(object $entity): void
    {
        $this->newEntities[spl_object_hash($entity)] = $entity;
    }

    public function scheduleForDelete(object $entity): void
    {
        $this->removedEntities[spl_object_hash($entity)] = $entity;
    }

    public function scheduleIfDirty(object $entity): void
    {
        $oid = spl_object_hash($entity);

        if (!isset($this->originalData[$oid])) {
            return; // new entity or unmanaged
        }

        $current = $this->em->getEntitySerializer()->serialize($entity);
        $original = $this->originalData[$oid];

        if ($current !== $original) {
            $this->dirtyEntities[$oid] = $entity;
        }
    }

    // -------------------------------------------------
    // Flush Logic: Transactions / Batches / Instants
    // -------------------------------------------------

    public function flush(): void
    {
        $client = $this->em->getClient();
        $metadataLoader = $this->em->getMetadataLoader();
        $serializer = $this->em->getEntitySerializer();
        $logger = $this->em->getLogger();

        // ----------------------------
        // AUTOMATIC DIRTY DETECTION
        // ----------------------------
        // todo: uncomment and fix
        foreach ($this->identityMap as $oid => $entity) {
            // Skip new or removed entities
            if (isset($this->newEntities[$oid]) || isset($this->removedEntities[$oid])) {
                continue;
            }

            $current = $serializer->serialize($entity);
            $original = $this->originalData[$oid];

            $logger?->debug(__METHOD__, [
                'oid' => $oid,
                'entity' => $entity,
                'original' => $original,
                'current' => $current,
                'updated' => $current !== $original,
            ]);

            if ($current !== $original) {
                $this->dirtyEntities[$oid] = $entity;
            }
        }

        $writes = [];

        // INSERTS
        foreach ($this->newEntities as $entity) {
            $class = $entity::class;
            $metadata = $metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();
            $item = $serializer->serialize($entity);

//            $logger->critical(__METHOD__ . ':insert', $item);

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
        foreach ($this->dirtyEntities as $entity) {
            $class = $entity::class;
            $metadata = $metadataLoader->getEntityMetadata($class);
            $table = $metadata->getTable();

            $newData = $serializer->serialize($entity);
            $oldData = $this->originalData[spl_object_hash($entity)];

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

//            $logger->critical(__METHOD__ . ':update', $newData);

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

        // DELETES
        foreach ($this->removedEntities as $entity) {
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
        }


        $logger->debug(__METHOD__, [
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
                die;
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
            $table = array_key_first($write[array_key_first($write)]);
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

                $client->batchWriteItem(['RequestItems' => [$table => $items]]);
            }
        }

        $this->afterFlush();
    }

    private function afterFlush(): void
    {
        $this->newEntities = [];
        $this->removedEntities = [];
        $this->dirtyEntities = [];
    }

    public function clear(): void
    {
        $this->identityMap = [];
        $this->originalData = [];
        $this->newEntities = [];
        $this->removedEntities = [];
        $this->dirtyEntities = [];
    }
}