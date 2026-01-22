<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Intl\CountryProvider;
use LogicException;

class TelegramChannelValidator
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    /**
     * @param TelegramChannel $channel
     * @return void
     */
    public function validateTelegramChannel(TelegramChannel $channel): void
    {
        if ($channel->primary()) {
            $existing = $this->repository->findOnePrimaryNonDeletedByChannel($channel);

            if ($existing !== null && $existing->getId() !== $channel->getId()) {
                throw new LogicException(sprintf('"%s" Primary Telegram channel already has the same settings', $existing->getUsername()));
            }
        }

        $countryCode = $channel->getCountryCode();
        $localeCode = $channel->getLocaleCode();

        $country = $this->countryProvider->getCountry($countryCode);

        if (!in_array($localeCode, $country->getLocaleCodes(), true)) {
            throw new LogicException(sprintf('"%s" locale does not belongs to "%s" country', $localeCode, $countryCode));
        }
    }
}