<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrCorrupt\UkrCorruptPerson;
use App\Model\Search\UkrCorrupt\UkrCorruptPersons;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UkrCorruptSearchProviderTest extends KernelTestCase
{
//    use SearchProviderTrait;

    public function testFake()
    {
        $this->markTestSkipped();
    }

    protected static SearchProviderName $searchProviderName = SearchProviderName::ukr_corrupts;

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

        yield 'person name & not cyrillic' => [
            'type' => SearchTermType::person_name,
            'term' => 'any word',
            'context' => [
                'countryCode' => 'ua',
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
    }

    public function searchDataProvider(): Generator
    {
        $yieldPerson = static fn (string $name): array => [
            'type' => SearchTermType::person_name,
            'term' => $name,
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new UkrCorruptPersons([
                    new UkrCorruptPerson(
                        punishmentType: 'Судове рішення',
                        entityType: 'Фізична особа',
                        lastName: 'Андрущенко',
                        firstName: 'Вікторія',
                        patronymic: 'Вікторівна',
                        offenseName: 'Несвоєчасне подання декларацій особи, уповноваженої на виконання функцій держави або місцевого самоврядування за минулий рік (після звільнення)',
                        punishment: 'Визнано винною у вчиненні корупційного адміністративного правопорушення, передбаченого ч.1 ст. 172-6 КУпАП та накладено адміністративне стягнення у виді штрафу у розмірі п’ятдесяти неоподатковуваних мінімумів доходів громадян, що дорівнює 850 (вісімсот п’ятдесят) гривень.',
                        courtCaseNumber: '363/5182/21',
                        sentenceDate: new DateTimeImmutable('2022-01-20'),
                        punishmentStart: new DateTimeImmutable('2022-02-01'),
                        courtName: 'Вишгородський районний суд Київської області',
                        codexArticles: ['Порушення вимог фінансового контролю'],
                    ),
                ]),
            ],
        ];

        yield 'person name & last first' => $yieldPerson('Андрущенко Вікторія');

        yield 'person name & first last' => $yieldPerson('Вікторія Андрущенко');

        yield 'person name & last first patronymic' => $yieldPerson('Андрущенко Вікторія Вікторівна');

        yield 'person name & first last patronymic' => $yieldPerson('Вікторія Андрущенко Вікторівна');
    }
}