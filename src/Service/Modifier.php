<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Modifiers;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\TimeProvider;
use App\Service\Util\String\MbUcFirster;
use App\Service\Util\String\SecretsAdder;
use DateTimeInterface;

class Modifier
{
    public function __construct(
        private readonly SecretsAdder $secretsAdder,
        private readonly TimeProvider $timeProvider,
        private readonly CountryProvider $countryProvider,
        private readonly MbUcFirster $mbUcFirster,
    )
    {
    }

    public function create(): Modifiers
    {
        return new Modifiers();
    }

    public function boldModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : ('<b>' . $any . '</b>');
    }

    public function italicModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : ('<i>' . $any . '</i>');
    }

    public function underlineModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : ('<u>' . $any . '</u>');
    }

    public function linkModifier(?string $href): callable
    {
        return static fn ($any): ?string => $any === null ? null : (empty($href) ? $any : ('<a href="' . $href . '">' . $any . '</a>'));
    }

    public function secretsModifier(int $position = 2, string $char = '*', int $count = 3): callable
    {
        return fn ($any): ?string => $any === null ? null : $this->secretsAdder->addSecrets($any, position: $position, char: $char, count: $count);
    }

    public function wordSecretsModifier(string|array $excepts = null, string $char = '*'): callable
    {
        return fn ($any): ?string => $any === null ? null : $this->secretsAdder->addWordSecrets($any, excepts: $excepts, char: $char);
    }

    public function greenWhiteModifier(string $active = null, string $inactive = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🟢 ' . $active) : ('⚪️ ' . $inactive));
    }

    public function redModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : '🔴';
    }

    public function redWhiteModifier(string $active = null, string $inactive = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🔴 ' . $active) : ('⚪️ ' . $inactive));
    }

    public function redGreenModifier(string $red = null, string $green = null): callable
    {
        return fn ($any): ?string => $any === null ? null : rtrim($any ? ('🔴 ' . $red) : ('🟢 ' . $green));
    }

    public function slashesModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : addslashes($any);
    }

    public function conditionalModifier($condition): callable
    {
        return static fn ($any): mixed => $condition ? $any : null;
    }

    public function bracketsModifier(?string $add): callable
    {
        return fn ($any): ?string => $any === null ? null : (empty($add) ? $any : ($any . ' [ ' . $add . ' ]'));
    }

    public function filterModifier(): callable
    {
        return static fn (?array $any): ?array => empty($any) ? null : array_filter($any);
    }

    public function implodeModifier(string $separator): callable
    {
        return static fn (?array $any): ?string => empty($any) ? null : implode($separator, $any);
    }

    public function mapModifier(callable $callback): callable
    {
        return static fn (?array $any): ?array => empty($any) ? null : array_map($callback, $any);
    }

    public function linesModifier(): callable
    {
        return static function (?array $any): ?string {
            if (empty($any)) {
                return null;
            }

            foreach ($any as $item) {
                if (empty($item)) {
                    continue;
                }

                $item = trim($item);
                $item = preg_replace('/\s+/', ' ', $item);

                $noTagsItem = strip_tags($item);
                $noTagsItem = trim($noTagsItem);

                if (!empty($item) && !empty($noTagsItem)) {
                    $items[] = $item;
                }
            }

            return '◻️ ' . implode("\n▫️ ", $items);
        };
    }

    public function implodeLinesModifier(callable $callback): callable
    {
        return function (?array $any) use ($callback): ?string {
            if (empty($any)) {
                return null;
            }

            $items = [];

            foreach ($any as $item) {
                $items[] = $this->linesModifier()($callback($item));
            }

            return implode("\n\n", $items);
        };
    }

    public function emptyNullModifier(): callable
    {
        return static fn ($any) => empty($any) ? null : $any;
    }

    public function prependModifier($prepend): callable
    {
        return static fn ($any) => $any === null ? null : ($prepend === null ? $any : ($prepend . $any));
    }

    public function appendModifier($append): callable
    {
        return static fn ($any) => $any === null ? null : ($append === null ? $any : ($any . $append));
    }

    public function newLineModifier(int $times = 1): callable
    {
        return static fn ($any): ?string => $any === null ? null : ($any . str_repeat("\n", $times));
    }

    public function datetimeModifier(string|int $format, string $timezone = null, string $locale = null): callable
    {
        if (is_string($format)) {
            return static fn (?DateTimeInterface $any): ?string => $any === null ? null : $any->format($format);
        }

        return fn (?DateTimeInterface $any): ?string => $any === null ? null : $this->timeProvider->format($format, $any, $timezone, $locale);
    }

    public function trimModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : trim($any);
    }

    public function numberFormatModifier(int $decimals = 0, ?string $decimalSeparator = '.', ?string $thousandsSeparator = ','): callable
    {
        return static fn ($any): ?string => $any === null ? null : number_format((float) $any, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public function markModifier(): callable
    {
        return static fn ($any): ?string => match (true) {
            $any < 0 => '🔴',
            $any === 0 => '⚪️',
            $any > 0 => '🟢',
            default => null,
        };
    }

    public function ratingModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : str_repeat('⭐️', (int) round((float) $any));
    }

    public function spoilerModifier(): callable
    {
        return static fn ($any): ?string => $any === null ? null : ('<tg-spoiler>' . $any . '</tg-spoiler>');
    }

    public function countryModifier(string $locale = null): callable
    {
        return fn ($any): ?string => $any === null ? null : $this->countryProvider->getCountryComposeName($any, localeCode: $locale);
    }

    public function ucFirstModifier(): callable
    {
        return fn ($any): ?string => $any === null ? null : $this->mbUcFirster->mbUcFirst($any);
    }

    public function nullModifier(): callable
    {
        return static fn ($any): mixed => $any;
    }
}
