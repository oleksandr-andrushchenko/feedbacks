<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use Closure;
use Longman\TelegramBot\Entities\Update;

readonly class TelegramBotCommandHandler extends TelegramBotHandler implements TelegramBotHandlerInterface
{
    public function __construct(
        private string $name,
        private Closure $callback,
        private bool $menu = false,
        private ?string $key = null,
        bool $force = false,
    )
    {
        $force2 = $force;
        parent::__construct(
            static fn (Update $update, bool $force = false): bool => $update->getMessage()?->getText() === $name && (($force && $force2) || !$force),
            $this->callback
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMenu(): bool
    {
        return $this->menu;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}