<?php
declare(strict_types=1);

namespace App\Service\Util\Array;

class ArrayPosEraser
{
    public function erasePos(array $input, string|int $pos): array
    {
        if (!array_key_exists($pos, $input)) {
            return $input;
        }

        unset($input[$pos]);

        return $input;
    }
}
