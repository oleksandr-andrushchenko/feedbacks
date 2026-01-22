<?php

declare(strict_types=1);

namespace App\Repository\Intl;

use App\Entity\Intl\Level1Region;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<Level1Region>
 * @method Level1RegionDoctrineRepository getDoctrine()
 * @property Level1RegionDoctrineRepository doctrine
 * @method Level1RegionDynamodbRepository getDynamodb()
 * @property Level1RegionDynamodbRepository $dynamodb
 * @method Level1Region|null findOneByCountryAndName(string $countryCode, string $name)
 * @method Level1Region[] findByCountry(string $countryCode)
 */
class Level1RegionRepository extends EntityRepository
{
}
