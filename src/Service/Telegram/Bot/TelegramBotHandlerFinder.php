<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Model\Telegram\TelegramBotErrorHandler;
use App\Model\Telegram\TelegramBotFallbackHandler;
use App\Model\Telegram\TelegramBotHandlerInterface;
use Longman\TelegramBot\Entities\Update;

class TelegramBotHandlerFinder
{
    /**
     * @param Update $update
     * @param TelegramBotHandlerInterface[] $handlers
     * @param bool $force
     * @return TelegramBotHandlerInterface|null
     */
    public function findOneHandler(Update $update, array $handlers, bool $force = false): ?TelegramBotHandlerInterface
    {
        $args = [
            $update,
            $force,
        ];

        foreach ($handlers as $handler) {
            if (call_user_func_array($handler->getSupports(), $args) === true) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @param TelegramBotHandlerInterface[] $handlers
     * @return TelegramBotHandlerInterface|null
     */
    public function findOneFallbackHandler(array $handlers): ?TelegramBotHandlerInterface
    {
        foreach ($handlers as $handler) {
            if ($handler instanceof TelegramBotFallbackHandler) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @param TelegramBotHandlerInterface[] $handlers
     * @return TelegramBotHandlerInterface|null
     */
    public function findOneErrorHandler(array $handlers): ?TelegramBotHandlerInterface
    {
        foreach ($handlers as $handler) {
            if ($handler instanceof TelegramBotErrorHandler) {
                return $handler;
            }
        }

        return null;
    }
}