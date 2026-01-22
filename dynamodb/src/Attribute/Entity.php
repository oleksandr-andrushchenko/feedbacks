<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

use Attribute as PHPAttribute;

#[PHPAttribute(PHPAttribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public ?AbstractKey $partitionKey,
        public ?AbstractKey $sortKey = null,
        /**
         * @var array<int, AbstractIndex>
         */
        public array $indexes = [],
        public ?string $table = null,
    )
    {
//        if ('' === $this->table) {
//            throw new InvalidArgumentException(
//                sprintf('Attribute argument %s::table must not be empty', $this::class)
//            );
//        }
    }
}
