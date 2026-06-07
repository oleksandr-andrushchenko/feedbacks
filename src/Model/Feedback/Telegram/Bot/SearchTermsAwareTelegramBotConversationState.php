<?php
declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Model\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;

abstract class SearchTermsAwareTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        protected ?SearchTermsTransfer $searchTerms = null,
    )
    {
        parent::__construct($step);
    }

    /**
     * @deprecated - Use getSearchTerms instead
     */
    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerms === null ? null : ($this->searchTerms->hasItems() ? $this->searchTerms->getFirstItem() : null);
    }

    /**
     * @deprecated - Use setSearchTerms instead
     */
    public function setSearchTerm(?SearchTermTransfer $searchTerm): static
    {
        $this->searchTerms = $searchTerm === null ? null : new SearchTermsTransfer([$searchTerm]);
    }

    public function setSearchTerms(?SearchTermsTransfer $searchTerms): static
    {
        $this->searchTerms = $searchTerms;

        return $this;
    }

    public function getSearchTerms(): ?SearchTermsTransfer
    {
        return $this->searchTerms;
    }
}
