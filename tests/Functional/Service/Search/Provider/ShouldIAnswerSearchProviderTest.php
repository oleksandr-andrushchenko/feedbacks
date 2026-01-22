<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReview;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReviews;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShouldIAnswerSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::should_i_answer;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::email,
            'term' => 'any',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'phone & not us' => [
            'type' => SearchTermType::phone_number,
            'term' => '380969603103',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'phone & ok' => [
            'type' => SearchTermType::phone_number,
            'term' => '15613144508',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'no phone rating & no phone code' => [
            'type' => SearchTermType::phone_number,
            'term' => '5613144509',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => [
                new ShouldIAnswerReviews(
                    header: '+1 561-314-4509 fixed or mobile line',
                    info: 'Phone number 5613144509 has no rating yet.',
                    score: 0,
                    items: []
                ),
            ],
        ];

        yield 'with phone rating & with phone code' => [
            'type' => SearchTermType::phone_number,
            'term' => '15613146709',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => [
                new ShouldIAnswerReviews(
                    header: '+1 561-314-6709 NEGATIVE SCAM CALL fixed or mobile line United States, Florida',
                    info: 'Phone number 5613146709 has negative rating. Single user rated it as negative . Approximated caller location is BOCA RATON, PALM BEACH, Florida. ZIP code is 33431. It\'s registered in DELTACOM, INC. - FL. This phone number is mostly categorized as Scam call (1 times).',
                    score: -1,
                    items: [
                        new ShouldIAnswerReview(
                            name: 'Scam call',
                            author: 'SIA User',
                            rating: 1,
                            datePublished: new DateTimeImmutable('2018-12-11 06:52:04'),
                            description: 'Scammer/late caller/disrespectful'
                        ),
                    ]
                ),
            ],
        ];
    }
}