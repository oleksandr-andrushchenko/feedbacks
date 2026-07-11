<?php
declare(strict_types=1);

namespace App\Enum\Feedback;

enum SearchTermType: int
{
    case unknown = 0;

    case instagram_username = 21;
    case threads_username = 31;
    case facebook_username = 29;
    case telegram_username = 22;
    case twitter_username = 23;
    case tiktok_username = 24;
    case youtube_username = 25;
    case onlyfans_username = 26;
    case reddit_username = 27;
    case vkontakte_username = 28;
    case messenger_username = 20;

    case messenger_profile_url = 10;
    case url = 30;
    case email = 40;
    case phone_number = 50;
    case person_name = 60;
    case organization_name = 70;
    case place_name = 80;
    case car_number = 1;
    case tax_number = 2;

    public const array base = [
        self::messenger_profile_url,
        self::messenger_username,
        self::phone_number,
        self::car_number,
        self::tax_number,
        self::email,
        self::url,
        self::place_name,
        self::person_name,
        self::organization_name,
    ];

    public const array known_messengers = [
        self::instagram_username,
        self::threads_username,
        self::facebook_username,
        self::telegram_username,
        self::twitter_username,
        self::tiktok_username,
        self::youtube_username,
        self::onlyfans_username,
        self::reddit_username,
        self::vkontakte_username,
    ];

    public const array messengers = [
        ...self::known_messengers,
        self::messenger_username,
        self::messenger_profile_url,
    ];

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
