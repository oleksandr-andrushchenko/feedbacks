<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Generator;

class CreateFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @dataProvider startDataProvider
     */
    public function testStart(string $input): void
    {
        $this->bootDefaultFixtures();

        $this->typeText($input)
            ->shouldSeeStateStep($this->getConversation(), CreateFeedbackTelegramBotConversation::STEP_MEDIA_QUERIED)
            ->shouldSeeReply('upload_media')
            ->shouldSeeButtons($this->nextButton(), $this->cancelButton())
        ;
    }

    public function startDataProvider(): Generator
    {
        yield 'button' => ['input' => $this->command('create')];
        yield 'input' => ['input' => FeedbackTelegramBotGroup::CREATE];
    }

    public function testSkipMediaMovesToDetailsStep(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_MEDIA_QUERIED)
        );

        $this->typeText($this->nextButton())
            ->shouldSeeStateStep($conversation, CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('input_details')
            ->shouldSeeButtons($this->prevButton(), $this->cancelButton())
        ;
    }

    public function testMediaUploadMovesToDetailsStepAndStoresMedia(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_MEDIA_QUERIED)
        );

        $this->typePhoto()
            ->shouldSeeStateStep($conversation, CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('input_details')
            ->shouldSeeButtons($this->prevButton(), $this->cancelButton())
        ;

        $state = $this->getSerializer()->denormalize(
            $conversation->getState(),
            CreateFeedbackTelegramBotConversationState::class
        );

        $this->assertCount(1, $state->getMedia());
    }

    public function testMediaUploadAtDetailsStepIsStoredWithoutRestartingMediaStep(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typePhoto('photo_file_id_2', 'photo_file_unique_id_2', 'album_1')
            ->shouldSeeStateStep($conversation, CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        ;

        $state = $this->getSerializer()->denormalize(
            $conversation->getState(),
            CreateFeedbackTelegramBotConversationState::class
        );

        $this->assertCount(1, $state->getMedia());
        $this->assertSame('album_1', $state->getMedia()[0]->getGroupId());
    }

    public function testDetailsSuccessCreatesFeedbackAndStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $details = 'great experience with instasd';

        $this->typeText($details)
            ->shouldSeeReply('created', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
        $this->assertNotEmpty($conversation->getState()['created_id'] ?? null);

        $state = $this->getSerializer()->denormalize(
            $conversation->getState(),
            CreateFeedbackTelegramBotConversationState::class
        );

        $this->assertSame($details, $state->getDetails());
        $this->assertSame(Rating::satisfied, $state->getRating());
        $this->assertSame('instasd', $state->getSearchTerms()->getFirstItem()->getText());
        $this->assertSame(SearchTermType::instagram_username, $state->getSearchTerms()->getFirstItem()->getType());
    }

    public function testDetailsWithoutSearchTermsCreatesFeedbackAndStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $details = 'no_terms general experience without identifiers';

        $this->typeText($details)
            ->shouldSeeReply('created', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
        $this->assertNotEmpty($conversation->getState()['created_id'] ?? null);

        $state = $this->getSerializer()->denormalize(
            $conversation->getState(),
            CreateFeedbackTelegramBotConversationState::class
        );

        $this->assertSame($details, $state->getDetails());
        $this->assertSame(Rating::satisfied, $state->getRating());
        $this->assertFalse($state->getSearchTerms()?->hasItems() ?? false);
    }

    public function testDetailsExtractionFailureKeepsConversationAtDetailsStep(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
        );

        $this->typeText('extract_fail')
            ->shouldSeeStateStep($conversation, CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
            ->shouldSeeReply('error', 'input_details')
            ->shouldSeeButtons($this->prevButton(), $this->cancelButton())
        ;
    }

    public function testCreateConfirmCreatesFeedbackAndStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
                ->setDetails('great experience with instasd')
                ->setRating(Rating::satisfied)
                ->setSearchTerms(new SearchTermsTransfer([
                    new SearchTermTransfer('instasd', SearchTermType::instagram_username),
                ]))
        );

        $this->typeText('✅ keyboard.create_confirm')
            ->shouldSeeReply('created', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
        $this->assertNotEmpty($conversation->getState()['created_id'] ?? null);
    }

    public function testCreateConfirmCreatesFeedbackWithoutSearchTerms(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
                ->setDetails('general experience without identifiers')
                ->setRating(Rating::satisfied)
        );

        $this->typeText('✅ keyboard.create_confirm')
            ->shouldSeeReply('created', ...$this->chooseActionReplies())
            ->shouldSeeButtons(...$this->chooseActionButtons())
        ;

        $this->assertConversationInactive($conversation);
        $this->assertNotEmpty($conversation->getState()['created_id'] ?? null);
    }

    public function testCancelStopsConversation(): void
    {
        $this->bootDefaultFixtures();

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_DETAILS_QUERIED)
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
