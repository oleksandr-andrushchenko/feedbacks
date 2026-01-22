<?php

declare(strict_types=1);

namespace OA\Dynamodb\Metadata;

use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class MetadataLoader
{
    public function __construct(
        /**
         * array<string, array<string, mixed>>
         */
        protected array $cache = [],
        protected array $defaults = [],
    )
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @throws ReflectionException
     */
    public function getClassMetadata(string $class): ClassMetadata
    {
        if (isset($this->cache[__METHOD__][$class])) {
            return $this->cache[__METHOD__][$class];
        }

        $reflection = new ReflectionClass($class);

        $classProperties = $this->getClassProperties($reflection);

        $metadata = new ClassMetadata($classProperties);

        $this->cache[__METHOD__][$class] = $metadata;

        return $metadata;
    }

    public function getDefaultTable(): ?string
    {
        return $this->defaults['table'] ?? null;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function getEntityMetadata(string $class): EntityMetadata
    {
        if (isset($this->cache[__METHOD__][$class])) {
            return $this->cache[__METHOD__][$class];
        }

        $reflection = new ReflectionClass($class);

        $classAttributes = $this->getClassAttributes($reflection);
        $entityAttribute = $classAttributes[Entity::class] ?? null;

        if (!$entityAttribute instanceof Entity) {
            throw new MetadataException(sprintf('No %s attribute declared for class "%s"', Entity::class, $class));
        }

        if ($entityAttribute->table === null) {
            $entityAttribute->table = $this->getDefaultTable();
        }

        $propertyAttributes = $this->getPropertyAttributes($reflection);
        $attributes = [];

        foreach ($propertyAttributes as $prop => $attrs) {
            foreach ($attrs as $attr) {
                if ($attr instanceof Attribute) {
                    $attributes[$prop] = $attr;
                }
            }
        }

        $metadata = new EntityMetadata($entityAttribute, $attributes);

        $this->cache[__METHOD__][$class] = $metadata;

        return $metadata;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return array<string, ReflectionProperty>
     */
    private function getClassProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $properties[$property->getName()] = $property;
        }

        return $properties;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return array<class-string, object>
     */
    private function getClassAttributes(ReflectionClass $reflection): array
    {
        $attributes = [];

        $parentReflection = $reflection->getParentClass();

        if ($parentReflection instanceof ReflectionClass) {
            $attributes = $this->getClassAttributes($parentReflection);
        }

        foreach ($reflection->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            $attributes[$instance::class] = $instance;
        }

        return $attributes;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return array<string, array<int, object>>
     */
    private function getPropertyAttributes(ReflectionClass $reflection): array
    {
        $attributes = [];

        $parentReflection = $reflection->getParentClass();

        if ($parentReflection instanceof ReflectionClass) {
            $attributes = $this->getPropertyAttributes($parentReflection);
        }

        foreach ($reflection->getProperties() as $property) {
            $propertyAttributes = $property->getAttributes();

            if (!empty($propertyAttributes)) {
                $attributes[$property->getName()] = array_map(
                    static fn (ReflectionAttribute $attribute): object => $attribute->newInstance(),
                    $propertyAttributes,
                );
            }
        }

        return $attributes;
    }
}
