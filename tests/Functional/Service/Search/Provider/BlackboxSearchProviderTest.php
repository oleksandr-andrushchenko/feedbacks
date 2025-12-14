<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\Blackbox\BlackboxFeedback;
use App\Model\Search\Blackbox\BlackboxFeedbacks;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BlackboxSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::blackbox;

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

        yield 'person name & ok' => [
            'type' => SearchTermType::person_name,
            'term' => 'Олександр Петренко',
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
        yield 'person name & surname only & many matches' => [
            'type' => SearchTermType::person_name,
            'term' => 'Солтис',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedbacks([
                    new BlackboxFeedback(
                        'Солтис Василій',
                        'https://blackbox.net.ua/0636357466',
                        '0636357466',
                        phoneFormatted: '+38 (063) 635-74-66',
                        comment: 'Клиент не забрал груз. Отправитель понес убытки за транспортировку.',
                        date: new DateTimeImmutable('2019-09-04'),
                        city: 'Київ',
                        warehouse: 'Відділення №30 (до 30 кг): вул. Привокзальна, 12',
                        cost: '2',
                        type: 'Нова Пошта'
                    ),
                ]),
            ],
        ];

        yield 'person name & surname and name & single match' => [
            'type' => SearchTermType::person_name,
            'term' => 'Андрущенко Аліна',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedback(
                    'Андрущенко Аліна',
                    'https://blackbox.net.ua/0961562339',
                    '0961562339',
                    phoneFormatted: '+38 (096) 156-23-39',
                    comment: 'Клиент не забрал груз. Отправитель понес убытки за транспортировку.',
                    date: new DateTimeImmutable('2021-10-02'),
                    city: 'Випасне (Білгород-Дністровський р-н)',
                    warehouse: 'Відділення №1: вул. Кишинівська, 159а',
                    cost: '46',
                    type: 'Нова Пошта'
                ),
            ],
        ];

        yield 'phone number & single match' => [
            'type' => SearchTermType::phone_number,
            'term' => '380932300040',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new BlackboxFeedback(
                    'Андрущенко Алексей',
                    'https://blackbox.net.ua/0932300040',
                    '0932300040',
                    phoneFormatted: '+38 (093) 230-00-40',
                    comment: 'Клиент не забрал посылку. Не отвечал на звонки и сообщения. Были понесены убытки на возврате отправления. Будьте внимательны с этим клиентом',
                    date: new DateTimeImmutable('2021-11-13'),
                    city: 'Бровари',
                    warehouse: 'Відділення №14 (до 30 кг на одне місце): бульв. Незалежності, 16',
                    cost: '46',
                    type: 'Нова Пошта'
                ),
            ],
        ];
    }
}