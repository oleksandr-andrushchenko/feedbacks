<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

abstract class AbstractIndex
{
    public function __construct(public string $name)
    {
        if ('' === $this->name) {
            throw new InvalidArgumentException(
                sprintf('Attribute argument %s::name must not be empty', $this::class)
            );
        }
    }
}
