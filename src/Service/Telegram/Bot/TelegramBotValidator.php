<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Intl\CountryProvider;
use LogicException;

class TelegramBotValidator
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return void
     * @throws LogicException
     */
    public function validateTelegramBot(TelegramBot $bot): void
    {
        if ($bot->primary()) {
            $existing = $this->telegramBotRepository->findOnePrimaryNonDeletedByBot($bot);

            if ($existing !== null && $existing->getId() !== $bot->getId()) {
                throw new LogicException(sprintf('"%s" Primary Telegram bot already has the same settings', $existing->getUsername()));
            }
        }

        $countryCode = $bot->getCountryCode();
        $localeCode = $bot->getLocaleCode();

        $country = $this->countryProvider->getCountry($countryCode);

        if (!in_array($localeCode, $country->getLocaleCodes(), true)) {
            throw new LogicException(sprintf('"%s" locale does not belongs to "%s" country', $localeCode, $countryCode));
        }
    }
}