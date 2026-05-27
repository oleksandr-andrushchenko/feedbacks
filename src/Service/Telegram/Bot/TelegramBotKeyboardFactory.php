<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class TelegramBotKeyboardFactory
{
    public function createTelegramKeyboard(...$buttons): Keyboard
    {
//        return $this->createTelegramInlineKeyboard(...$buttons);
        return new Keyboard([
            'keyboard' => array_map(static fn ($button) => is_array($button) ? $button : [$button], $buttons),
            'is_persistent' => true,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
            'selective' => true,
        ]);
    }

    public function createTelegramButton(string $text, bool $requestLocation = false): KeyboardButton
    {
//        return $this->createTelegramInlineButton($text);
        return new KeyboardButton([
            'text' => $text,
            'request_location' => $requestLocation,
        ]);
    }

    public function createTelegramInlineKeyboard(...$buttons): InlineKeyboard
    {
        return new InlineKeyboard([
            'inline_keyboard' => array_map(static fn ($button) => is_array($button) ? $button : [$button], $buttons),
        ]);
    }

    public function createTelegramInlineButton(string $text): InlineKeyboardButton
    {
        return new InlineKeyboardButton([
            'text' => $text,
            'callback_data' => $text,
        ]);
    }
}
