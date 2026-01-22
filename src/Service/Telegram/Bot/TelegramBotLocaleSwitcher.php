<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Model\Intl\Locale;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\LocaleSwitcher;

class TelegramBotLocaleSwitcher
{
    public function __construct(
        private readonly LocaleSwitcher $localeSwitcher,
    )
    {
    }

    public function syncLocale(TelegramBot $bot, Request $request): void
    {
        $messengerUser = $bot->getMessengerUser();
        $user = $bot->getUser();

        $localeCode = null;

        if ($messengerUser?->getId() === null) {
            $localeCode = $bot->getEntity()->getLocaleCode();
        }

        $localeCode ??= $user?->getLocaleCode();
        $localeCode ??= $this->localeSwitcher->getLocale();

        $user?->setLocaleCode($localeCode);
        $this->setLocale($localeCode);
        $request->setLocale($this->localeSwitcher->getLocale());
    }

    public function switchLocale(null|string|Locale $locale): void
    {
        $localeCode = $locale === null ? null : (is_string($locale) ? $locale : $locale->getCode());

        if ($localeCode === null) {
            $this->localeSwitcher->reset();;
        } else {
            $this->setLocale($localeCode);
        }
    }

    public function setLocale(string|Locale $locale): void
    {
        $localeCode = is_string($locale) ? $locale : $locale->getCode();
//        setlocale(LC_TIME, $localeCode);
        $this->localeSwitcher->setLocale($localeCode);
    }
}