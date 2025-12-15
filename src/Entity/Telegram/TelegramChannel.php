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
    new PartitionKey('TELEGRAM_CHANNEL', ['id']),
    new SortKey('M'),
    [
        new GlobalIndex(
            'TELEGRAM_CHANNELS_BY_USERNAME',
            new PartitionKey(null, ['username'], 'telegram_channel_username_pk')
        ),
        new GlobalIndex(
            'TELEGRAM_CHANNELS_BY_GROUP_COUNTRY_LOCALE',
            new PartitionKey('TELEGRAM_CHANNEL', [], 'telegram_channel_pk'),
            new SortKey(null, ['group', 'countryCode', 'localeCode'], 'telegram_channel_group_country_locale_sk'),
        ),
    ]
)]
class TelegramChannel implements Stringable
{
    public function __construct(
        #[Attribute('telegram_channel_id')]
        private string $id,
        #[Attribute]
        private readonly string $username,
        #[Attribute]
        private TelegramBotGroupName $group,
        #[Attribute]
        private string $name,
        #[Attribute('country_code')]
        private string $countryCode,
        #[Attribute('locale_code')]
        private string $localeCode,
        #[Attribute('level_1_region_id')]
        private ?string $level1RegionId = null,
        #[Attribute('chat_id')]
        private ?string $chatId = null,
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

    public function getLevel1RegionId(): ?string
    {
        return $this->level1RegionId;
    }

    public function setLevel1RegionId(?string $level1RegionId): self
    {
        $this->level1RegionId = $level1RegionId;

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

    public function getChatId(): ?string
    {
        return $this->chatId;
    }

    public function setChatId(?string $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }

    public function primary(): bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
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
