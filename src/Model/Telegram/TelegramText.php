<?php

declare(strict_types=1);

namespace App\Model\Telegram;

readonly class TelegramText
{
    public function __construct(
        private string $text,
    )
    {
    }

    public function getSanitizedValue(): string
    {
        // replace multi spaces with single space
        $input = preg_replace('/ +/', ' ', $this->text);

        // remove empty lines
        return implode("\n", array_filter(explode("\n", $input), static fn (string $line): bool => !in_array($line, ['', ' '], true)));
    }
}
