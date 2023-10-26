<?php

declare(strict_types=1);

namespace App\Tests\Traits\Address;

use App\Repository\Address\Level1RegionRepository;

trait Level1RegionRepositoryProviderTrait
{
    public function getLevel1RegionRepository(): Level1RegionRepository
    {
        return static::getContainer()->get('app.level_1_region_repository');
    }
}