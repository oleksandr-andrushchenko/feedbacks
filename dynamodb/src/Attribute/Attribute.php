<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

use Attribute as PHPAttribute;

#[PHPAttribute(PHPAttribute::TARGET_PROPERTY)]
class Attribute
{
    public function __construct(
        protected ?string $name = null,
        protected bool $ignoreIfNull = true,
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function ignoreIfNull(): bool
    {
        return $this->ignoreIfNull;
    }
}
