<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrMissedCar\UkrMissedCar;
use App\Tests\Traits\Search\SearchProviderTrait;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UkrMissedCarSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::ukr_missed_cars;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::tax_number,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'supported type & not ukr' => [
            'type' => SearchTermType::car_number,
            'term' => 'any',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'partial & ok' => [
            'type' => SearchTermType::car_number,
            'term' => 'АА',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'ok' => [
            'type' => SearchTermType::car_number,
            'term' => 'АА6279ОО',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'partial match' => [
            'type' => SearchTermType::car_number,
            'term' => 'АА6279',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                [
                    new UkrMissedCar(
                        'АА6279ОО',
                        region: 'ВОЛНОВАСЬКИЙ ВІДДІЛ',
                        model: 'MERCEDES-BENZ - 200 D',
                        chassisNumber: null,
                        bodyNumber: 'WDB1241201J097391',
                        color: 'СИНИЙ'
                    ),
                ],
            ],
        ];
    }
}