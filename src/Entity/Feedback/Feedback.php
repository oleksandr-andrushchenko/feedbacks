<?php

declare(strict_types=1);

namespace App\Entity\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('FEEDBACK', ['id']),
    new SortKey('META'),
)]
class Feedback
{
    private Collection $searchTerms;

    public function __construct(
        #[Attribute('feedback_id')]
        private ?string $id = null,
        private ?User $user = null,
        #[Attribute('user_id')]
        private ?string $userId = null,
        #[Attribute('country_code')]
        private ?string $countryCode = null,
        #[Attribute('local_code')]
        private ?string $localeCode = null,
        #[Attribute('has_active_subscription')]
        private ?bool $hasActiveSubscription = null,
        private ?MessengerUser $messengerUser = null,
        #[Attribute('messenger_user_id')]
        private ?string $messengerUserId = null,
        /** @var array<SearchTerm>|null $searchTerms */
        ?array $searchTerms = null,
        #[Attribute('search_term_ids')]
        private ?array $searchTermIds = null,
        #[Attribute]
        private ?Rating $rating = null,
        #[Attribute('text')]
        private ?string $text = null,
        #[Attribute('telegram_channel_message_ids')]
        private ?array $telegramChannelMessageIds = null,
        private ?TelegramBot $telegramBot = null,
        #[Attribute('telegram_bot_id')]
        private ?string $telegramBotId = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->searchTerms = new ArrayCollection($searchTerms ?? []);
        $this->searchTermIds = array_map(static fn ($term) => $term->getId(), $searchTerms ?? []);
        $this->userId ??= $this->user?->getId();
        $this->countryCode = $this->user?->getCountryCode();
        $this->localeCode = $this->user?->getLocaleCode();
        $this->hasActiveSubscription = $this->user?->hasActiveSubscription();
        $this->messengerUserId ??= $this->messengerUser?->getId();
        $this->telegramBotId ??= $this->telegramBot?->getId();
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
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

    public function getMessengerUserId(): ?string
    {
        return $this->messengerUserId;
    }

    public function setSearchTerms(?array $searchTerms): self
    {
        $this->searchTerms = new ArrayCollection($searchTerms ?? []);
        $this->searchTermIds = array_map(static fn ($term) => $term->getId(), $searchTerms);
        return $this;
    }

    public function addSearchTermId(string $id): self
    {
        if ($this->searchTermIds === null) {
            $this->searchTermIds = [];
        }
        $this->searchTermIds[] = $id;
        return $this;
    }

    public function setSearchTermIds(?array $ids): self
    {
        $this->searchTermIds = $ids;
        return $this;
    }

    /**
     * @return Collection<SearchTerm>
     */
    public function getSearchTerms(): Collection
    {
        return $this->searchTerms;
    }

    public function getSearchTermIds(): ?array
    {
        return $this->searchTermIds;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setHasActiveSubscription(?bool $hasActiveSubscription): self
    {
        $this->hasActiveSubscription = $hasActiveSubscription;
        return $this;
    }

    public function hasActiveSubscription(): ?bool
    {
        return $this->hasActiveSubscription;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function addTelegramChannelMessageId(int $messageId): self
    {
        if ($this->telegramChannelMessageIds === null) {
            $this->telegramChannelMessageIds = [];
        }
        if (!in_array($messageId, $this->telegramChannelMessageIds, true)) {
            $this->telegramChannelMessageIds[] = $messageId;
        }

        return $this;
    }

    public function getTelegramChannelMessageIds(): ?array
    {
        return $this->telegramChannelMessageIds;
    }

    public function setTelegramBotId(?string $telegramBotId): self
    {
        $this->telegramBotId = $telegramBotId;
        return $this;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function getTelegramBotId(): ?string
    {
        return $this->telegramBotId;
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
}
