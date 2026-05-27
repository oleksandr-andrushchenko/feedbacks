<?php
declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use App\Transfer\Telegram\TelegramChannelTransfer;

class TelegramChannelUpdater
{
    public function __construct(
        private readonly TelegramChannelValidator $validator,
    )
    {
    }

    /**
     * @param TelegramChannel $channel
     * @param TelegramChannelTransfer $channelTransfer
     * @return void
     */
    public function updateTelegramChannel(TelegramChannel $channel, TelegramChannelTransfer $channelTransfer): void
    {
        if ($channelTransfer->groupPassed()) {
            $channel->setGroup($channelTransfer->getGroup());
        }

        if ($channelTransfer->namePassed()) {
            $channel->setName($channelTransfer->getName());
        }

        if ($channelTransfer->countryPassed()) {
            $channel->setCountryCode($channelTransfer->getCountry()->getCode());
        }
        if ($channelTransfer->localePassed()) {
            $channel->setLocaleCode($channelTransfer->getLocale()->getCode());
        }
        if ($channelTransfer->level1RegionPassed()) {
            $channel->setLevel1RegionId($channelTransfer->getLevel1Region()?->getId());
        }
        if ($channelTransfer->primaryPassed()) {
            $channel->setPrimary($channelTransfer->primary());
        }
        if ($channelTransfer->chatIdPassed()) {
            $channel->setChatId($channelTransfer->getChatId());
        }

        $this->validator->validateTelegramChannel($channel);
    }
}