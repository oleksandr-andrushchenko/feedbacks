<?php
declare(strict_types=1);

namespace App\Enum\Messenger;

enum Messenger: int
{
    case unknown = 0;
    case telegram = 1;
    case instagram = 2;
    case facebook = 8;
    case twitter = 3;
    case tiktok = 4;
    case youtube = 5;
    case onlyfans = 6;
    case reddit = 7;
    case vkontakte = 9;
}
