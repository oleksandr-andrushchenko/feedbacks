<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\RestartConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use Generator;

class RestartTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @param string $input
     * @return void
     * @dataProvider startDataProvider
     */
    public function testStart(string $input): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this
            ->typeText($input)
            ->shouldSeeActiveConversation(
                RestartConversationTelegramBotConversation::class,
                new TelegramBotConversationState(RestartConversationTelegramBotConversation::STEP_CONFIRM_QUERIED)
            )
            ->shouldSeeReply(
                'query.confirm',
            )
            ->shouldSeeButtons(
                $this->yesButton(),
                $this->noButton(),
            )
        ;
    }

    public function startDataProvider(): Generator
    {
        yield 'button' => [
            'input' => $this->command('restart'),
        ];

        yield 'input' => [
            'input' => FeedbackTelegramBotGroup::RESTART,
        ];
    }

    public function testConfirmStep(): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->createConversation(
            RestartConversationTelegramBotConversation::class,
            new TelegramBotConversationState(RestartConversationTelegramBotConversation::STEP_CONFIRM_QUERIED)
        );

        $this
            ->typeText($this->yesButton())
            ->shouldSeeReply(
                ...$this->okReplies(),
            )
            ->shouldSeeButtons(
                ...$this->chooseActionButtons(),
            )
            ->shouldNotSeeActiveConversation()
        ;
    }
}