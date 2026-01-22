<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Closure;

interface TelegramBotHandlerInterface
{
    public function getSupports(): Closure;

    public function getCallback(): Closure;
}
