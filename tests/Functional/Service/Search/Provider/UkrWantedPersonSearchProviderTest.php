<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrWantedPerson\UkrWantedPerson;
use App\Model\Search\UkrWantedPerson\UkrWantedPersons;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UkrWantedPersonSearchProviderTest extends KernelTestCase
{
//    use SearchProviderTrait;

    public function testFake()
    {
        $this->markTestSkipped();
    }

    protected static SearchProviderName $searchProviderName = SearchProviderName::ukr_wanted_persons;

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
        yield 'many matches' => [
            'type' => SearchTermType::person_name,
            'term' => 'АНДРУЩЕНКО Олександр',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new UkrWantedPersons([
                    new UkrWantedPerson(
                        'АНДРУЩЕНКО',
                        'ОЛЕКСАНДР',
                        ukrPatronymic: 'МИХАЙЛОВИЧ',
                        rusSurname: 'АНДРУЩЕНКО',
                        rusName: 'АЛЕКСАНДР',
                        rusPatronymic: 'МИХАЙЛОВИЧ',
                        gender: null,
                        region: 'ШОСТКИНСЬКИЙ ВІДДІЛ ПОЛІЦІЇ ГУНП В СУМСЬКІЙ ОБЛАСТІ',
                        bornAt: new DateTimeImmutable('1973-10-13'),
                        photo: 'https://wanted.mvs.gov.ua/getphoto/person/?thumbnailImage&id=3023314580705560',
                        category: null,
                        absentAt: null,
                        absentPlace: null,
                        href: 'https://wanted.mvs.gov.ua/searchperson/details/?id=3023314580705560',
                        precaution: null,
                        codexArticle: null,
                        callTo: null
                    ),
                ]),
            ],
        ];

        yield 'single match' => [
            'type' => SearchTermType::person_name,
            'term' => 'АНДРУЩЕНКО Олександр МИХАЙЛОВИЧ',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new UkrWantedPerson(
                    'АНДРУЩЕНКО',
                    'ОЛЕКСАНДР',
                    ukrPatronymic: 'МИХАЙЛОВИЧ',
                    rusSurname: 'АНДРУЩЕНКО',
                    rusName: 'АЛЕКСАНДР',
                    rusPatronymic: 'МИХАЙЛОВИЧ',
                    gender: 'чоловіча',
                    region: 'ШОСТКИНСЬКИЙ ВІДДІЛ ПОЛІЦІЇ ГУНП В СУМСЬКІЙ ОБЛАСТІ',
                    bornAt: new DateTimeImmutable('1973-10-13'),
                    photo: 'https://wanted.mvs.gov.ua/getphoto/person/?id=3023314580705560',
                    category: 'ОСОБА, ЯКА ПЕРЕХОВУЄТЬСЯ ВІД ОРГАНІВ ДОСУДОВОГО РОЗСЛІДУВАННЯ',
                    absentAt: new DateTimeImmutable('2023-11-10'),
                    absentPlace: 'СУМСЬКА, ШОСТКИНСЬКИЙ, ШОСТКА',
                    href: null,
                    precaution: 'НЕ ЗАСТОСОВУВАВСЯ',
                    codexArticle: 'СТ.185 Ч.4',
                    callTo: '(054492-14-47 0503279148'
                ),
            ],
        ];
    }
}