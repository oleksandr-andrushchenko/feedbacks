<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use App\Service\IdGenerator;
use App\Service\ORM\EntityManager;
use App\Transfer\Telegram\TelegramChannelTransfer;

class TelegramChannelCreator
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly TelegramChannelValidator $validator,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function createTelegramChannel(TelegramChannelTransfer $channelTransfer): TelegramChannel
    {
        $channel = new TelegramChannel(
            $this->idGenerator->generateId(),
            $channelTransfer->getUsername(),
            $channelTransfer->getGroup(),
            $channelTransfer->getName(),
            $channelTransfer->getCountry()->getCode(),
            $channelTransfer->getLocale()?->getCode() ?? $channelTransfer->getCountry()->getLocaleCodes()[0],
            $channelTransfer->getLevel1Region()?->getId(),
            $channelTransfer->getChatId(),
            $channelTransfer->primary(),
        );

        $this->validator->validateTelegramChannel($channel);

        $this->entityManager->persist($channel);

        return $channel;
    }
}