<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\CleanTalk\CleanTalkEmail;
use App\Model\Search\CleanTalk\CleanTalkEmails;
use App\Tests\Traits\Search\SearchProviderTrait;
use DateTimeImmutable;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CleanTalkSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::clean_talk;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::phone_number,
            'term' => 'any',
            'context' => [],
            'expected' => false,
        ];

        yield 'email & ok' => [
            'type' => SearchTermType::email,
            'term' => 'lisiy17@ukr.net',
            'context' => [],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'not real email' => [
            'type' => SearchTermType::email,
            'term' => 'alex.snowgirlqweqweqw1eqwe@gmail.com',
            'context' => [],
            'expected' => [
                new CleanTalkEmails([
                    new CleanTalkEmail(
                        address: 'alex.snowgirlqweqweqw1eqwe@gmail.com',
                        href: 'https://cleantalk.org/blacklists/alex.snowgirlqweqweqw1eqwe@gmail.com',
                        attackedSites: 0,
                        blacklisted: true,
                        real: false,
                        disposable: true,
                        lastUpdate: (new DateTimeImmutable())->setTime(0, 0)
                    ),
                ]),
            ],
        ];

        yield 'real email' => [
            'type' => SearchTermType::email,
            'term' => 'lisiy17@ukr.net',
            'context' => [],
            'expected' => [
                new CleanTalkEmails([
                    new CleanTalkEmail(
                        address: 'lisiy17@ukr.net',
                        href: 'https://cleantalk.org/blacklists/lisiy17@ukr.net',
                        attackedSites: 0,
                        blacklisted: true,
                        real: false,
                        disposable: true,
                        lastUpdate: (new DateTimeImmutable())->setTime(0, 0)
                    ),
                ]),
            ],
        ];
    }
}