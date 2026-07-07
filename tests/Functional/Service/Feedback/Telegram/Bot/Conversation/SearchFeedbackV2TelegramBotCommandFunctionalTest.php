<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackV2TelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use Generator;

class SearchFeedbackV2TelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @dataProvider startDataProvider
     */
    public function testStart(string $input): void
    {
        $this->bootDefaultFixtures();

        $this->typeText($input)
            ->shouldSeeStateStep($this->getConversation(), SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('query.search_term')
            ->shouldSeeButtons($this->helpButton(), $this->cancelButton())
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
            SearchFeedbackV2TelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText('find feedback about instasd')
            ->shouldSeeReply('reply.will_notify', 'instasd', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
    }

    public function testDetailsExtractionFailureKeepsConversationAtDetailsStep(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            SearchFeedbackV2TelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText('extract_fail')
            ->shouldSeeStateStep($conversation, SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('reply.extraction_failed', 'query.search_term')
            ->shouldSeeButtons($this->helpButton(), $this->cancelButton())
        ;
    }

    public function testCancelStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            SearchFeedbackV2TelegramBotConversation::class,
            (new SearchFeedbackTelegramBotConversationState())
                ->setStep(SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText($this->cancelButton())
            ->shouldSeeReply(...$this->cancelReplies(), ...$this->chooseActionReplies())
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
