<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Telegram\Bot;

use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\AssertLoggedTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateHandlerTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateRepositoryProviderTrait;
use Monolog\Level;

class TelegramBotUpdateHandlerTest extends DatabaseTestCase
{
    use TelegramBotUpdateFixtureProviderTrait;
    use TelegramBotUpdateRepositoryProviderTrait;
    use AssertLoggedTrait;
    use TelegramBotUpdateHandlerTrait;

    public function testHandleTelegramBotUpdateStore(): void
    {
        // todo: uncomment & fix
        $this->markTestSkipped();
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);
        $updateId = 10;

        $this->handleTelegramBotUpdate(null, $this->getTelegramMessageUpdateFixture([
            'text' => 'any',
        ], updateId: $updateId));

        $this->assertLogged(Level::Info, 'Telegram update received');

        $updateRepository = $this->getTelegramBotUpdateRepository();

        $this->assertEquals(1, $updateRepository->count([]));
        $this->assertNotNull($updateRepository->find($updateId));
    }

    public function testHandleTelegramBotUpdateDuplicate(): void
    {
        // todo: uncomment & fix
        $this->markTestSkipped();
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
            Fixtures::TG_BOT_UPDATE_1,
            Fixtures::TG_BOT_UPDATE_2,
        ]);

        $updateRepository = $this->getTelegramBotUpdateRepository();
        $previousUpdateCount = $updateRepository->count([]);

        $this->handleTelegramBotUpdate(null, $this->getTelegramMessageUpdateFixture([
            'text' => 'any',
        ], updateId: 1));

        $this->assertLogged(Level::Info, 'Telegram update received');
        $this->assertLogged(Level::Debug, 'Duplicate telegram update received, processing aborted');

        $this->assertEquals($previousUpdateCount, $updateRepository->count([]));
    }
}
