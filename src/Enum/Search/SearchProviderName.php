<?php
declare(strict_types=1);

namespace App\Enum\Search;

enum SearchProviderName: int
{
    case feedbacks = 0;
    case clarity = 1;
    case searches = 2;
    case ukr_corrupts = 3;
    case ukr_missed = 4;
    case otzyvua = 5;
    case ukr_missed_cars = 6;
    case business_guide = 7;
    case ukr_wanted_persons = 8;
    case blackbox = 9;
    case twenty_second_floor = 10;
    case clean_talk = 11;
    case should_i_answer = 12;

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
