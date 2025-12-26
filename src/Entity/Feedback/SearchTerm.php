<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Enum\Feedback\SearchTermType;
use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('SEARCH_TERM', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'SEARCH_TERMS_BY_NORMALIZED_TEXT',
            new PartitionKey(null, ['normalizedText'], 'search_term_normalized_text_pk')
        ),
        new GlobalIndex(
            'SEARCH_TERMS_BY_CREATED',
            new PartitionKey('SEARCH_TERM', [], 'search_term_pk'),
            new SortKey(null, ['createdAt'], 'search_term_created_sk'),
        ),
    ]
)]
class SearchTerm
{
    public function __construct(
        #[Attribute('search_term_id')]
        private string $id,
        #[Attribute]
        private readonly string $text,
        #[Attribute('normalized_txt')]
        private readonly string $normalizedText,
        #[Attribute]
        private readonly SearchTermType $type,
        private ?MessengerUser $messengerUser = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
    )
    {
        $this->messengerUserId = $this->messengerUser?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
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

    public function setMessengerUserId(?string $messengerUserId): self
    {
        $this->messengerUserId = $messengerUserId;
        return $this;
    }

    public function setMessengerUser(?MessengerUser $messengerUser): self
    {
        $this->messengerUser = $messengerUser;
        return $this;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }
}
