<?php
declare(strict_types=1);

namespace App\Service\Util\Array;

class ArrayNullFilter
{
    public function filterNulls(?array $input): ?array
    {
        if ($input === null) {
            return null;
        }

        foreach ($input as $k => $v) {
            if (is_array($v)) {
                $input[$k] = $this->filterNulls($v);
            } elseif ($v === null) {
                unset($input[$k]);
            }
        }

        return $input;
    }
}
