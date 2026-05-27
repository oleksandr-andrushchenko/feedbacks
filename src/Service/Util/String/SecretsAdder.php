<?php
declare(strict_types=1);

namespace App\Service\Util\String;

class SecretsAdder
{
    public function addSecrets(string $text, int $position = 2, string $char = '*', int $count = 3): string
    {
        $length = mb_strlen($text);

        if ($length <= $count) {
            return str_repeat($char, $length);
        }

        if ($length - $position < $count) {
            $position = $length - $count;
        }

        return mb_substr($text, 0, $position) . str_repeat($char, $count) . mb_substr($text, $position + $count);
    }

    public function addWordSecrets(string $text, string|array $excepts = null, string $char = '*'): string
    {
        $excepts = $excepts === null ? [] : (is_string($excepts) ? [$excepts] : $excepts);
        $keep = array_merge(...array_map(static fn (string $except): array => preg_split('/[^0-9\p{L}]+/iu', $except), $excepts));

        $checker = static fn (string $a): bool => array_filter($keep, static fn (string $b): bool => strcmp(mb_strtolower($a), mb_strtolower($b)) === 0) != [];
        $replacer = static fn (array $m): string => $checker($m[0]) ? $m[0] : str_repeat($char, mb_strlen($m[0]));

        return preg_replace_callback('/[0-9\p{L}]+/iu', $replacer, $text);
    }
}