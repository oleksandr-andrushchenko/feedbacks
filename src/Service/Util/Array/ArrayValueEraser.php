<?php
declare(strict_types=1);

namespace App\Service\Util\Array;

class ArrayValueEraser
{
    public function __construct(
        private readonly ArrayPosEraser $posEraser,
    )
    {
    }

    public function eraseValue(array $input, mixed $value): array
    {
        while (true) {
            $pos = array_search($value, $input);

            if ($pos === false) {
                return $input;
            }

            $input = $this->posEraser->erasePos($input, $pos);
        }
    }
}
