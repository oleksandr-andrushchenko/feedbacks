<?php
declare(strict_types=1);

namespace App\Service\Telegram;

class TelegramLinkProvider
{
    public function getTelegramLink(string $username): string
    {
        return 'https://t.me/' . $username;
    }
}