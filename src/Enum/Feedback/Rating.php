<?php
declare(strict_types=1);

namespace App\Enum\Feedback;

enum Rating: int
{
    case unsatisfied = -1;
    case neutral = 0;
    case satisfied = 1;

    public static function random(): self
    {
        return self::cases()[array_rand(self::cases())];
    }
}
