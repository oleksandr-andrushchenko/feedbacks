<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Group;

use App\Enum\Telegram\TelegramBotGroupName;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TelegramBotGroupFactory
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function createTelegramBotGroup(TelegramBotGroupName $name): TelegramBotGroupInterface
    {
        return $this->serviceLocator->get($name->name);
    }
}
