<?php

declare(strict_types=1);

namespace OA\Dynamodb\Metadata;

use ReflectionProperty;

readonly class ClassMetadata
{
    public function __construct(
        /** @var array<string, ReflectionProperty> */
        protected array $properties,
    )
    {
    }

    public function has(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    public function get(string $property): ?ReflectionProperty
    {
        return $this->properties[$property] ?? null;
    }
}
