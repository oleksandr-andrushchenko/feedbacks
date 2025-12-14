<?php

declare(strict_types=1);

namespace OA\Dynamodb\Metadata;

use OA\Dynamodb\Attribute\AbstractIndex;
use OA\Dynamodb\Attribute\AbstractKey;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;

class EntityMetadata
{
    public function __construct(
        protected Entity $entityAttribute,
        /**
         * @var array<string, Attribute>
         */
        protected array $propertyAttributes,
        protected array $defaults = [],
    )
    {
    }

    public function getTable(): ?string
    {
        return $this->entityAttribute->table;
    }

    public function getPartitionKey(): AbstractKey
    {
        return $this->entityAttribute->partitionKey;
    }

    public function getSortKey(): ?AbstractKey
    {
        return $this->entityAttribute->sortKey;
    }

    /**
     * @return array<int, AbstractIndex>
     */
    public function getIndexes(): array
    {
        return $this->entityAttribute->indexes;
    }

    /**
     * @return array<string, Attribute>
     */
    public function getPropertyAttributes(): array
    {
        return $this->propertyAttributes;
    }

    public function hasProperty(string $property): bool
    {
        if (array_key_exists($property, $this->propertyAttributes)) {
            return true;
        }

        return false;
    }

    public function getProperty(string $property): ?Attribute
    {
        return $this->propertyAttributes[$property] ?? null;
    }
}
