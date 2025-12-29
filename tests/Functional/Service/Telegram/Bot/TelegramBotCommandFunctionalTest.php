<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Tests\Fixtures;
use App\Tests\Traits\IdGeneratorProviderTrait;
use DateTimeImmutable;

class TelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use IdGeneratorProviderTrait;

    public function testDeletedWithoutReplacementTelegramBotRequest(): void
    {
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setDeletedAt(new DateTimeImmutable());
        $this->getEntityManager()->flush();

        $this->typeText('any');

        $this->assertEmpty($this->getTelegramBotMessageSender()->getCalls());
    }

    public function testDeletedWithReplacementTelegramBotRequest(): void
    {
        // todo: uncomment & fix
        $this->markTestSkipped();
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setDeletedAt(new DateTimeImmutable());
        $entityManager = $this->getEntityManager();
        $newBot = $this->copyBot($bot);
        $entityManager->persist($newBot);
        $entityManager->flush();

        $this->typeText('any');

        $this->shouldSeeReply(
            'reply.use_primary',
            $newBot->getUsername(),
            $newBot->getName(),
        );
    }

    public function testNonPrimaryWithoutReplacementTelegramBotRequest(): void
    {
        // todo: uncomment & fix
        $this->markTestSkipped();
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setPrimary(false);
        $this->getEntityManager()->flush();

        $this->typeText('any');

        $this->assertEmpty($this->getTelegramBotMessageSender()->getCalls());
    }

    public function testNonPrimaryWithReplacementTelegramBotRequest(): void
    {
        // todo: uncomment & fix
        $this->markTestSkipped();
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneNonDeletedByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setPrimary(false);
        $entityManager = $this->getEntityManager();
        $newBot = $this->copyBot($bot);
        $entityManager->persist($newBot);
        $entityManager->flush();

        $this->typeText('any');

        $this->shouldSeeReply(
            'reply.use_primary',
            $newBot->getUsername(),
            $newBot->getName(),
        );
    }

    private function copyBot(TelegramBot $bot): TelegramBot
    {
        return new TelegramBot(
            id: $this->getIdGenerator()->generateId(),
            username: $bot->getUsername() . '_copy',
            group: $bot->getGroup(),
            name: $bot->getName() . ' Copy',
            token: 'token',
            countryCode: $bot->getCountryCode(),
            localeCode: $bot->getLocaleCode()
        );
    }
}