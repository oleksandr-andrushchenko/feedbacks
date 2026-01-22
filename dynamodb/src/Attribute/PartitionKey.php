<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

class PartitionKey extends AbstractKey
{
    public function __construct(
        ?string $prefix = null,
        ?array $fields = [],
        ?string $name = 'pk',
        ?string $delimiter = '#',
    )
    {
        parent::__construct($prefix ,$fields, $name, $delimiter);
    }
}
