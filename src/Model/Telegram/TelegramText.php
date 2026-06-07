<?php
declare(strict_types=1);

namespace App\Model\Telegram;

use Stringable;

readonly class TelegramText implements Stringable
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
        return implode(PHP_EOL, array_filter(explode(PHP_EOL, $input), static fn (string $line): bool => !in_array($line, ['', ' '], true)));
    }

    public function getRawValue(): string
    {
        return $this->text;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
