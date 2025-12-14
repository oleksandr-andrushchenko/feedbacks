<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Messenger\Messenger;
use App\Transfer\Messenger\MessengerUserTransfer;
use App\Service\Intl\CountryProvider;
use App\Service\Messenger\MessengerUserUpserter;
use App\Service\User\UserUpserter;

class TelegramBotMessengerUserUpserter
{
    public function __construct(
        private readonly TelegramBotUserProvider $telegramBotUserProvider,
        private readonly MessengerUserUpserter $messengerUserUpserter,
        private readonly UserUpserter $userUpserter,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function upsertTelegramMessengerUser(TelegramBot $bot): ?MessengerUser
    {
        $user = $this->telegramBotUserProvider->getTelegramUserByUpdate($bot->getUpdate());

        if ($user === null) {
            return null;
        }

        $countryCode = $bot->getEntity()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $transfer = new MessengerUserTransfer(
            Messenger::telegram,
            (string) $user->getId(),
            username: $user->getUsername(),
            name: trim($user->getFirstName() . ' ' . $user->getLastName()),
            countryCode: $country->getCode(),
            localeCode: $bot->getEntity()->getLocaleCode(),
            currencyCode: $country->getCurrencyCode(),
            timezone: $country->getTimezones()[0] ?? null,
            botId: $bot->getEntity()->getId()
        );
        $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($transfer);
        $this->userUpserter->upsertUserByMessengerUser($messengerUser, $transfer);

        return $messengerUser;
    }
}