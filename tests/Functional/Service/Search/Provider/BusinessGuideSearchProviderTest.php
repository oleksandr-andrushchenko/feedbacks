<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\BusinessGuide\BusinessGuideEnterprise;
use App\Model\Search\BusinessGuide\BusinessGuideEnterprises;
use App\Tests\Traits\Search\SearchProviderTrait;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BusinessGuideSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::business_guide;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::facebook_username,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & not ukr' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'person name & first name only' => [
            'type' => SearchTermType::person_name,
            'term' => 'Степан',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & middle name only' => [
            'type' => SearchTermType::person_name,
            'term' => 'Сергійович',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & last name only & ok' => [
            'type' => SearchTermType::person_name,
            'term' => 'Власюк',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'person name & first and middle names & ok' => [
            'type' => SearchTermType::person_name,
            'term' => 'Степан Сергійович',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'org name & not ukr' => [
            'type' => SearchTermType::organization_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'org name & ok' => [
            'type' => SearchTermType::organization_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'place name & not ukr' => [
            'type' => SearchTermType::place_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'place name & ok' => [
            'type' => SearchTermType::place_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'phone number & not ukr' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603103',
            'context' => [],
            'expected' => false,
        ];

        yield 'phone number & not ukr code' => [
            'type' => SearchTermType::phone_number,
            'term' => '15613145672',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'phone number & ok' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603103',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'org name & many matches' => [
            'type' => SearchTermType::organization_name,
            'term' => 'Ерідон',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BusinessGuideEnterprises([
                    new BusinessGuideEnterprise(
                        'ЕРІДОН ТЕХ, ТОВ',
                        'https://eridon-tech.business-guide.com.ua',
                        desc: 'Комбайни зернозбиральні / Комбайни кормозбиральні / Комбайни бурякозбиральні / Трактори /...',
                        address: 'Київська обл.'
                    ),
                ]),
            ],
        ];

        yield 'phone number & single match' => [
            'type' => SearchTermType::phone_number,
            'term' => '380636356979',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BusinessGuideEnterprise(
                    'АНДРУЩЕНКО ОЛЕКСАНДР СЕРГІЙОВИЧ, ФОП',
                    'https://8000994519.business-guide.com.ua',
                    country: 'Українa',
                    phone: '380636356979',
                    ceo: 'Андрущенко Олександр Сергійович',
                    sectors: ['Розроблення стандартного програмного забезпечення'],
                    address: '02140, м. Київ, вул. Бориса Гмирі, 9в кв. 55',
                    number: '8000994519'
                ),
            ],
        ];
    }
}