<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

class GlobalIndex extends AbstractIndex
{
    public function __construct(
        string $name,
        public AbstractKey $partitionKey,
        public ?AbstractKey $sortKey = null,
    ) {
        parent::__construct($name);
    }
}
