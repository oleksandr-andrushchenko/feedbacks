<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use App\Enum\Telegram\TelegramBotGroupName;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

#[Entity(
    new PartitionKey('TELEGRAM_BOT', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'TELEGRAM_BOTS_BY_USERNAME',
            new PartitionKey(null, ['username'], 'telegram_bot_username_pk')
        ),
        new GlobalIndex(
            'TELEGRAM_BOTS_BY_GROUP_COUNTRY_LOCALE',
            new PartitionKey('TELEGRAM_BOT', [], 'telegram_bot_pk'),
            new SortKey(null, ['group', 'countryCode', 'localeCode'], 'telegram_bot_group_country_locale_sk'),
        ),
    ]
)]
class TelegramBot implements Stringable
{
    public function __construct(
        #[Attribute('telegram_bot_id')]
        private string $id,
        #[Attribute]
        private readonly string $username,
        #[Attribute]
        private TelegramBotGroupName $group,
        #[Attribute]
        private string $name,
        #[Attribute]
        private string $token,
        #[Attribute('country_code')]
        private string $countryCode,
        #[Attribute('locale_code')]
        private string $localeCode,
        #[Attribute('check_updates')]
        private bool $checkUpdates = false,
        #[Attribute('check_requests')]
        private bool $checkRequests = false,
        #[Attribute('accept_payments')]
        private bool $acceptPayments = false,
        #[Attribute('admin_ids')]
        private array $adminIds = [],
        #[Attribute('admin_only')]
        private bool $adminOnly = true,
        #[Attribute('descriptions_synced')]
        private bool $descriptionsSynced = false,
        #[Attribute('webhook_synced')]
        private bool $webhookSynced = false,
        #[Attribute('commands_synced')]
        private bool $commandsSynced = false,
        #[Attribute]
        private ?bool $primary = true,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
        #[Attribute('updated_at')]
        private ?DateTimeInterface $updatedAt = null,
        #[Attribute('deleted_at')]
        private ?DateTimeInterface $deletedAt = null,
    )
    {
        if ($this->primary !== true) {
            $this->primary = null;
        }
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getGroup(): TelegramBotGroupName
    {
        return $this->group;
    }

    public function setGroup(TelegramBotGroupName $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function checkUpdates(): bool
    {
        return $this->checkUpdates;
    }

    public function setCheckUpdates(bool $checkUpdates): self
    {
        $this->checkUpdates = $checkUpdates;

        return $this;
    }

    public function checkRequests(): bool
    {
        return $this->checkRequests;
    }

    public function setCheckRequests(bool $checkRequests): self
    {
        $this->checkRequests = $checkRequests;

        return $this;
    }

    public function acceptPayments(): bool
    {
        return $this->acceptPayments;
    }

    public function setAcceptPayments(bool $acceptPayments): self
    {
        $this->acceptPayments = $acceptPayments;

        return $this;
    }

    public function getAdminIds(): array
    {
        return array_map(static fn ($adminId): int => (int) $adminId, $this->adminIds);
    }

    public function setAdminIds(array $adminIds): self
    {
        foreach ($adminIds as $adminId) {
            $this->addAdminId($adminId);
        }

        return $this;
    }

    public function addAdminId(string|int $adminId): self
    {
        $this->adminIds[] = (int) $adminId;
        $this->adminIds = array_filter(array_unique($this->adminIds));

        return $this;
    }

    public function adminOnly(): bool
    {
        return $this->adminOnly;
    }

    public function setAdminOnly(bool $adminOnly): self
    {
        $this->adminOnly = $adminOnly;

        return $this;
    }

    public function descriptionsSynced(): bool
    {
        return $this->descriptionsSynced;
    }

    public function setDescriptionsSynced(bool $descriptionsSynced): self
    {
        $this->descriptionsSynced = $descriptionsSynced;

        return $this;
    }

    public function webhookSynced(): bool
    {
        return $this->webhookSynced;
    }

    public function setWebhookSynced(bool $webhookSynced): self
    {
        $this->webhookSynced = $webhookSynced;

        return $this;
    }

    public function commandsSynced(): bool
    {
        return $this->commandsSynced;
    }

    public function setCommandsSynced(bool $commandsSynced): self
    {
        $this->commandsSynced = $commandsSynced;

        return $this;
    }

    public function primary(): ?bool
    {
        return $this->primary;
    }

    public function setPrimary(?bool $primary): self
    {
        $this->primary = $primary;

        return $this;
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

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
