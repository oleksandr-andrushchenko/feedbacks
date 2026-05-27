<?php
declare(strict_types=1);

namespace App\Enum\Feedback;

enum FeedbackSubscriptionPlanName: int
{
    case one_day = 0;
    case three_days = 1;
    case one_month = 2;
    case six_months = 3;
    case one_year = 4;

    public static function random(): self
    {
        return self::cases()[array_rand(self::cases())];
    }

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $enum) {
            if ($enum->name === $name) {
                return $enum;
            }
        }

        return null;
    }
}
