<?php
declare(strict_types=1);

namespace App\Service\Util\Array;

class ArrayKeyQuoter
{
    public function quoteKeys(array $input, string $char = '%'): array
    {
        return array_combine(
            array_map(static fn (string $key): string => $char . $key . $char, array_keys($input)),
            array_values($input)
        );
    }
}
