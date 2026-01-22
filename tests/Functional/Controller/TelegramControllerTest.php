<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Enum\Site\SitePage;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\Telegram\Bot\TelegramBotRepositoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateHandlerMockProviderTrait;
use App\Tests\Traits\WebClientProviderTrait;
use Exception;
use Generator;

class TelegramControllerTest extends DatabaseTestCase
{
    use WebClientProviderTrait;
    use TelegramBotUpdateHandlerMockProviderTrait;
    use TelegramBotUpdateFixtureProviderTrait;
    use TelegramBotRepositoryProviderTrait;

    /**
     * @param SitePage $page
     * @return void
     * @dataProvider pageDataProvider
     */
    public function testPage(SitePage $page): void
    {
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);

        $client = $this->getWebClient();

        $client->jsonRequest('GET', sprintf('/telegram/%s/%s', $bot->getUsername(), $page->value));

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertStringContainsString($bot->getUsername(), $response->getContent());
    }

    public function pageDataProvider(): Generator
    {
        yield 'index' => [
            'page' => SitePage::INDEX,
        ];

        yield 'privacy policy' => [
            'page' => SitePage::PRIVACY_POLICY,
        ];

        yield 'terms of use' => [
            'page' => SitePage::TERMS_OF_USE,
        ];

        yield 'contacts' => [
            'page' => SitePage::CONTACTS,
        ];
    }

    public function testWebhook(): void
    {
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $client = $this->getWebClient();

        $this->getTelegramBotUpdateHandlerMock();

        $client->jsonRequest(
            'POST',
            sprintf('/telegram/%s/webhook', Fixtures::BOT_USERNAME_1),
            $this->getTelegramBotUpdateFixture()->jsonSerialize()
        );

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('ok', $response->getContent());
    }

    public function testWebhookFailure(): void
    {
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $client = $this->getWebClient();

        $this->getTelegramBotUpdateHandlerMock()
            ->method('handleTelegramBotUpdate')
            ->willThrowException(new Exception())
        ;

        $client->jsonRequest(
            'POST',
            sprintf('/telegram/%s/webhook', Fixtures::BOT_USERNAME_1),
            $this->getTelegramBotUpdateFixture()->jsonSerialize()
        );

        $response = $client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('failed', $response->getContent());
    }
}
