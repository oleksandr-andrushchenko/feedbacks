<?php

declare(strict_types=1);

namespace App\Model\Telegram;

class TelegramBotConversationState
{
    public function __construct(
        protected ?int $step = null,
    )
    {
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function setStep(?int $step): static
    {
        $this->step = $step;

        return $this;
    }
}
