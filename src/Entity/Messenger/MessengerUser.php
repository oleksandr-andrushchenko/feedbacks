<?php

declare(strict_types=1);

namespace App\Entity\Messenger;

use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('MESSENGER_USER', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'MESSENGER_USERS_BY_MESSENGER_IDENTIFIER',
            new PartitionKey(null, ['messenger', 'identifier'], 'messenger_user_messenger_identifier_pk')
        ),
        new GlobalIndex(
            'MESSENGER_USERS_BY_MESSENGER_USERNAME',
            new PartitionKey(null, ['messenger', 'username'], 'messenger_user_messenger_username_pk')
        ),
        new GlobalIndex(
            'MESSENGER_USERS_BY_USER',
            new PartitionKey(null, ['userId'], 'messenger_user_user_pk')
        ),
    ]
)]
class MessengerUser implements Stringable
{
    /** @var array<string>|null */
    #[Attribute('telegram_bot_ids')]
    private ?array $telegramBotIds = null;
    #[Attribute('username_history')]
    private ?array $usernameHistory = null;
    #[Attribute('user_id')]
    private ?string $userId = null;
    #[Attribute('created_at')]
    private ?DateTimeInterface $createdAt = null;
    #[Attribute('updated_at')]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(
        #[Attribute('messenger_user_id')]
        private string $id,
        #[Attribute]
        private readonly Messenger $messenger,
        #[Attribute]
        private readonly string $identifier,
        #[Attribute]
        private ?string $username = null,
        #[Attribute]
        private ?string $name = null,
        private ?User $user = null,
        #[Attribute('show_extended_keyboard')]
        private ?bool $showExtendedKeyboard = null,
    )
    {
        $this->userId = $this->user?->getId();
        $this->showExtendedKeyboard = $this->showExtendedKeyboard === true ? true : null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->userId = $user?->getId();

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function showExtendedKeyboard(): ?bool
    {
        return $this->showExtendedKeyboard;
    }

    public function setShowExtendedKeyboard(?bool $showExtendedKeyboard): self
    {
        $this->showExtendedKeyboard = $showExtendedKeyboard === true ? true : null;

        return $this;
    }

    /**
     * @return int[]|null
     */
    public function getTelegramBotIds(): ?array
    {
        return $this->telegramBotIds === null ? null : array_map('intval', $this->telegramBotIds);
    }

    public function addTelegramBotId(string $botId): self
    {
        if ($this->telegramBotIds === null) {
            $this->telegramBotIds = [];
        }

        $this->telegramBotIds[] = $botId;
        $this->telegramBotIds = array_filter(array_unique($this->telegramBotIds));

        return $this;
    }

    public function removeTelegramBotId(string $botId): self
    {
        if ($this->telegramBotIds === null) {
            return $this;
        }

        $this->telegramBotIds = array_unique(array_diff($this->telegramBotIds, [$botId]));

        return $this;
    }

    public function addUsernameHistory(string $username): self
    {
        if ($this->usernameHistory === null) {
            $this->usernameHistory = [];
        }

        $this->usernameHistory[] = $username;
        $this->usernameHistory = array_filter(array_unique($this->usernameHistory));

        return $this;
    }

    public function getUsernameHistory(): ?array
    {
        return $this->usernameHistory;
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

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}