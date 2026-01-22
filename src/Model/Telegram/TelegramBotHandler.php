<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Closure;

readonly abstract class TelegramBotHandler implements TelegramBotHandlerInterface
{
    public function __construct(
        private Closure $supports,
        private Closure $callback,
    )
    {
    }

    public function getSupports(): Closure
    {
        return $this->supports;
    }

    public function getCallback(): Closure
    {
        return $this->callback;
    }
}