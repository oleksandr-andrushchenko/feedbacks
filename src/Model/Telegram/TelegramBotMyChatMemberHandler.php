<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Closure;
use Longman\TelegramBot\Entities\Update;

readonly class TelegramBotMyChatMemberHandler extends TelegramBotHandler implements TelegramBotHandlerInterface
{
    public function __construct(
        private Closure $callback,
    )
    {
        parent::__construct(
            static fn (Update $update): bool => $update->getMyChatMember() !== null,
            $this->callback
        );
    }

}