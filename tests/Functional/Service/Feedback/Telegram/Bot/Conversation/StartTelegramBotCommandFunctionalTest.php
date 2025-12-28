<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Intl\CountryProviderTrait;

class StartTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use CountryProviderTrait;

    public function testStart(): void
    {
        $this->bootFixtures([
            Fixtures::TG_BOT_1,
        ]);

        $this->typeText(FeedbackTelegramBotGroup::START);

        $this->assertNotNull($this->getUpdateMessengerUser());
        $user = $this->getUpdateMessengerUser()->getUser();
        $this->assertNotNull($user);

        $bot = $this->getBot()->getEntity();
        $botCountry = $this->getCountryProvider()->getCountry($bot->getCountryCode());

        $this->assertEquals($botCountry->getCode(), $user->getCountryCode());
        $this->assertEquals($botCountry->getCurrencyCode(), $user->getCurrencyCode());
        $this->assertEquals($bot->getLocaleCode(), $user->getLocaleCode());
        $this->assertEquals($botCountry->getTimezones()[0] ?? null, $user->getTimezone());

        $this
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(
                'title',
//                'agreements',
            )
            ->shouldSeeButtons(
                ...$this->chooseActionButtons()
            )
        ;
    }
}