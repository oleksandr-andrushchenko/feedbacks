<?php
declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Transfer\Feedback\SearchTermsTransfer;

class SearchFeedbackTelegramBotConversationState extends SearchTermsAwareTelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        ?SearchTermsTransfer $searchTerms = null,
        private ?string $details = null,
    )
    {
        parent::__construct($step, $searchTerms);
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }
}
