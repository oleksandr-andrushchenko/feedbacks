<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Closure;
use Longman\TelegramBot\Entities\Update;

readonly class TelegramBotErrorHandler extends TelegramBotHandler implements TelegramBotHandlerInterface
{
    public function __construct(
        Closure $callback,
    )
    {
        parent::__construct(
            static fn (Update $update): bool => false,
            $callback
        );
    }
}