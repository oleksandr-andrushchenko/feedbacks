<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Feedback\SearchTermType;
use DateTimeImmutable;
use DateTimeInterface;

class FeedbackSearchTerm
{
    public function __construct(
        private readonly string $text,
        private readonly string $normalizedText,
        private readonly SearchTermType $type,
        private readonly ?MessengerUser $messengerUser,
        private readonly DateTimeInterface $createdAt = new DateTimeImmutable(),
        private ?int $id = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getNormalizedText(): string
    {
        return $this->normalizedText;
    }

    public function getType(): SearchTermType
    {
        return $this->type;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
