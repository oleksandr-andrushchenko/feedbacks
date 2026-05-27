<?php
declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Transfer\Feedback\SearchTermsTransfer;

class SearchFeedbackTelegramBotConversationState extends SearchTermAwareTelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermsTransfer $searchTerms = null,
    )
    {
        parent::__construct($step);

        $this->setSearchTerms($this->searchTerms);
    }

    public function setSearchTerms(?SearchTermsTransfer $searchTerms): self
    {
        $this->searchTerms = $searchTerms ?? new SearchTermsTransfer();

        return $this;
    }

    public function getSearchTerms(): SearchTermsTransfer
    {
        return $this->searchTerms;
    }
}
