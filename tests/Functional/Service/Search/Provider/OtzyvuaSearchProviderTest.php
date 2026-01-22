<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\Otzyvua\OtzyvuaFeedback;
use App\Model\Search\Otzyvua\OtzyvuaFeedbacks;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerms;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OtzyvuaSearchProviderTest extends KernelTestCase
{
//    use SearchProviderTrait;

    public function testFake()
    {
        $this->markTestSkipped();
    }

    protected static SearchProviderName $searchProviderName = SearchProviderName::otzyvua;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::person_name,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'supported type & not ukr' => [
            'type' => SearchTermType::organization_name,
            'term' => 'any',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'email & ok' => [
            'type' => SearchTermType::email,
            'term' => 'aaa.gmail.com',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'phone number & ok' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603102',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'org name & ok' => [
            'type' => SearchTermType::organization_name,
            'term' => 'Приват',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'place name & ok' => [
            'type' => SearchTermType::place_name,
            'term' => 'Майдан Незадежності',
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
            'term' => 'приват',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new OtzyvuaFeedbackSearchTerms([
                    new OtzyvuaFeedbackSearchTerm(
                        'Розовый носорог',
                        href: 'https://www.vidhuk.ua/uk/rozoviy-nosorog.html',
                        category: 'Бари',
                        rating: 5.0,
                        count: 2
                    ),
                ]),
            ],
        ];

        yield 'org name & many matches & full match found' => [
            'type' => SearchTermType::organization_name,
            'term' => 'приватбанк',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new OtzyvuaFeedbacks([
                    new OtzyvuaFeedback(
                        'Чи потрібно бути членом Gold клубу.',
                        href: 'https://www.vidhuk.ua/uk/privat-bank/review-1937020',
                        rating: 4,
                        authorName: 'Владимир Сидоров',
                        authorHref: 'https://www.vidhuk.ua/uk/user/7830',
                        description: 'Я клієнт банку 20 років Усі роки був прихильником привату,завжди мав хорошу кредитну історію,своєчасно вносив гроші.Тепер дожився до того,що банк вирішив лишити мене права оплати частинами.Зараз ліміт оплати 7000 гр.Це мертві гроші.Ні збільшити ні зменшити.Ощад дає опцію,але я не хочу.Що робити.',
                        createdAt: new DateTimeImmutable('2023-11-19')
                    ),
                ]),
            ],
        ];
    }
}