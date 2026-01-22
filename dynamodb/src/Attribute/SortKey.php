<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

class SortKey extends AbstractKey
{
    public function __construct(
        ?string $prefix = null,
        ?array $fields = [],
        ?string $name = 'sk',
        ?string $delimiter = '#',
    )
    {
        parent::__construct($prefix, $fields, $name, $delimiter);
    }
}
