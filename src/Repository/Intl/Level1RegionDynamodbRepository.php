<?php

declare(strict_types=1);

namespace App\Repository\Intl;

use App\Entity\Intl\Level1Region;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<Level1Region>
 */
class Level1RegionDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, Level1Region::class);
    }

    public function find(string $id): ?Level1Region
    {
        return $this->getOne(['id' => $id]);
    }

    public function findAll(): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('LEVEL_1_REGIONS_BY_COUNTRY_NAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'level_1_region_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'LEVEL_1_REGION',
                ])
        );
    }

    public function findOneByCountryAndName(string $countryCode, string $name): ?Level1Region
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('LEVEL_1_REGIONS_BY_COUNTRY_NAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                    '#sk = :sk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'level_1_region_pk',
                    '#sk' => 'level_1_region_country_code_name_sk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'LEVEL_1_REGION',
                    ':sk' => $countryCode . '#' . $name,
                ])
        );
    }

    public function findByCountry(string $countryCode): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('LEVEL_1_REGIONS_BY_COUNTRY_NAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'level_1_region_pk',
                    '#sk' => 'level_1_region_country_code_name_sk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'LEVEL_1_REGION',
                    ':sk' => $countryCode . '#',
                ])
        );
    }
}
