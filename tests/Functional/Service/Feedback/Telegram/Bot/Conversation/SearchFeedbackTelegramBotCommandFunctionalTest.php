<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use Generator;

class SearchFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @dataProvider startDataProvider
     */
    public function testStart(string $input): void
    {
        $this->bootDefaultFixtures();

        $this->typeText($input)
            ->shouldSeeStateStep($this->getConversation(), SearchFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('input_details')
            ->shouldSeeButtons($this->cancelButton())
        ;
    }

    public function startDataProvider(): Generator
    {
        yield 'button' => ['input' => $this->command('search')];
        yield 'input' => ['input' => FeedbackTelegramBotGroup::SEARCH];
    }

    public function testDetailsSuccessSearchesAndStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            SearchFeedbackTelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText('find feedback about instasd')
            ->shouldSeeReply('will_notify', 'instasd', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
    }

    public function testDetailsExtractionFailureKeepsConversationAtDetailsStep(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            SearchFeedbackTelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText('extract_fail')
            ->shouldSeeStateStep($conversation, SearchFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('error', 'input_details')
            ->shouldSeeButtons($this->cancelButton())
        ;
    }

    public function testCancelStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            SearchFeedbackTelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText($this->cancelButton())
            ->shouldSeeReply('canceled', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
    }

    private function bootDefaultFixtures(): void
    {
        $this->bootFixtures([
            Fixtures::USER_1,
            Fixtures::USER_2,
            Fixtures::USER_3,
            Fixtures::MESSENGER_USER_1_TELEGRAM,
            Fixtures::MESSENGER_USER_1_INSTAGRAM,
            Fixtures::MESSENGER_USER_2_TELEGRAM,
            Fixtures::MESSENGER_USER_2_INSTAGRAM,
            Fixtures::MESSENGER_USER_3_TELEGRAM,
            Fixtures::MESSENGER_USER_3_INSTAGRAM,
            Fixtures::TG_BOT_1,
        ]);
    }
}
