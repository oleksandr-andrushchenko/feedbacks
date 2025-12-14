<?php

declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Model\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermTransfer;

abstract class SearchTermAwareTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermTransfer $searchTerm = null,
    )
    {
        parent::__construct($step);
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?SearchTermTransfer $searchTerm): static
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }
}
