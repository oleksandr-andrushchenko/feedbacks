<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotUpdate;
use App\Repository\Telegram\Bot\TelegramBotUpdateRepository;
use App\Service\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class TelegramBotUpdateChecker
{
    public function __construct(
        private readonly TelegramBotUpdateRepository $telegramBotUpdateRepository,
        private readonly EntityManager $entityManager,
        private readonly LoggerInterface $logger,
        private readonly bool $checkDuplicates = false,
        private readonly bool $saveRequests = false,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @return bool
     */
    public function checkTelegramUpdate(TelegramBot $bot): bool
    {
        if (!$bot->getEntity()->checkUpdates()) {
            return false;
        }

        if ($this->checkDuplicates) {
            $update = $this->telegramBotUpdateRepository->findOneByUpdateId($bot->getUpdate()?->getUpdateId());

            if ($update !== null) {
                $this->logger->warning('Duplicate telegram update received, processing aborted', [
                    'name' => $bot->getEntity()->getGroup()->name,
                    'update_id' => $update->getId(),
                ]);

                return true;
            }
        }

        if ($this->saveRequests) {
            $update = new TelegramBotUpdate(
                (string) $bot->getUpdate()->getUpdateId(),
                $bot->getUpdate()->getRawData(),
                $bot->getEntity(),
            );
            $this->entityManager->persist($update);
        }

        return false;
    }
}