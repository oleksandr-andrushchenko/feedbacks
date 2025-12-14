<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Tests\Fixtures;
use App\Tests\Traits\EntityManagerProviderTrait;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\HttpFoundation\Request;

trait TelegramBotUpdateHandlerTrait
{
    use TelegramBotUpdateHandlerProviderTrait;
    use EntityManagerProviderTrait;
    use TelegramBotRegistryProviderTrait;
    use TelegramBotRepositoryProviderTrait;

    private function handleTelegramBotUpdate(?TelegramBot $bot, Update $update): void
    {
        if ($bot === null) {
            $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);
        }

        $this->getTelegramBotUpdateHandler()->handleTelegramBotUpdate($bot, new Request(content: $update->toJson()));
        $this->getEntityManager()->flush();
    }
}