<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

class LocalIndex extends AbstractIndex
{
    public function __construct(
        string $name,
        public AbstractKey $sortKey,
    ) {
        parent::__construct($name);
    }
}
