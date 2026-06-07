<?php
declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Enum\Feedback\Rating;
use App\Model\Telegram\TelegramPhoto;
use App\Model\Telegram\TelegramVideo;
use App\Transfer\Feedback\SearchTermsTransfer;

class CreateFeedbackTelegramBotConversationState extends SearchTermsAwareTelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        ?SearchTermsTransfer $searchTerms = null,
        private ?string $details = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?array $media = null,
        private ?string $createdId = null,
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

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMedia(): ?array
    {
        return $this->media;
    }

    public function setMedia(?array $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function addMedia(TelegramPhoto|TelegramVideo $media): static
    {
        $this->media[] = $media;

        return $this;
    }

    public function hasMedia(): bool
    {
        return count($this->media ?? []) > 0;
    }

    public function getCreatedId(): ?string
    {
        return $this->createdId;
    }

    public function setCreatedId(?string $createdId): static
    {
        $this->createdId = $createdId;

        return $this;
    }
}
