<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Service\IdGenerator;
use App\Transfer\Telegram\TelegramBotTransfer;
use App\Service\ORM\EntityManager;

class TelegramBotCreator
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly TelegramBotValidator $telegramBotValidator,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function createTelegramBot(TelegramBotTransfer $botTransfer): TelegramBot
    {
        $bot = new TelegramBot(
            $this->idGenerator->generateId(),
            $botTransfer->getUsername(),
            $botTransfer->getGroup(),
            $botTransfer->getName(),
            $botTransfer->getToken(),
            $botTransfer->getCountry()->getCode(),
            $botTransfer->getLocale()?->getCode() ?? $botTransfer->getCountry()->getLocaleCodes()[0],
            $botTransfer->checkUpdates(),
            $botTransfer->checkRequests(),
            $botTransfer->acceptPayments(),
            $botTransfer->getAdminIds(),
            $botTransfer->adminOnly(),
            $botTransfer->primary(),
        );

        $this->telegramBotValidator->validateTelegramBot($bot);

        $this->entityManager->persist($bot);

        return $bot;
    }
}