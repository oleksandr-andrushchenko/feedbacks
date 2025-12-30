<?php

declare(strict_types=1);

namespace App\Entity\Intl;

use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    new PartitionKey('LEVEL_1_REGION', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'LEVEL_1_REGIONS_BY_COUNTRY_NAME',
            new PartitionKey('LEVEL_1_REGION', [], 'level_1_region_pk'),
            new SortKey(null, ['countryCode', 'name'], 'level_1_region_country_code_name_sk'),
        ),
    ]
)]
class Level1Region
{
    public function __construct(
        #[Attribute('level_1_region_id')]
        private string $id,
        #[Attribute('country_code')]
        private readonly string $countryCode,
        #[Attribute]
        private readonly string $name,
        #[Attribute]
        private ?string $timezone = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }
}
