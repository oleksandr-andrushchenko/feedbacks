<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackSearch;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Model\Feedback\Telegram\Bot\LookupFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackLookupRepositoryProviderTrait;
use App\Tests\Traits\Feedback\SearchTermRepositoryProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use Generator;

class LookupFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use MessengerUserProfileUrlProviderTrait;
    use SearchTermRepositoryProviderTrait;
    use FeedbackLookupRepositoryProviderTrait;

    /**
     * @param string $input
     * @return void
     * @dataProvider startDataProvider
     */
    public function testStart(string $input): void
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

        $this
            ->typeText($input)
            ->shouldSeeStateStep(
                $this->getConversation(),
                LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED
            )
            ->shouldSeeReply(
                'query.search_term',
            )
            ->shouldSeeButtons(
                $this->helpButton(),
                $this->cancelButton(),
            )
        ;
    }

    public function startDataProvider(): Generator
    {
        yield 'button' => [
            'input' => $this->command('lookup'),
        ];

        yield 'input' => [
            'input' => FeedbackTelegramBotGroup::LOOKUP,
        ];
    }

    /**
     * @param SearchTermTransfer|null $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider searchTermStepDataProvider
     */
    public function testSearchTermStep(
        ?SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerm,
            LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep
        );
    }

    public function searchTermStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'text.not_blank'],
            ['multiple lines', "first_line\r\nsecond_line", 'text.single_line'],
            ['forbidden chars', 'qwasdйцуeqwe ; sdf', 'text.allowed_chars'],
            ['too short', 'ф', 'text.min_length'],
            ['too long', str_repeat('п', 256 + 1), 'text.max_length'],
        ];

        foreach ($validators as [$key, $input, $reply]) {
            yield 'type ' . $key => [
                'searchTerms' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeFirstSearchTermText' => null,
            ];
        }

        yield 'type & unknown type' => [
            'searchTerm' => null,
            'input' => $input = 'any_search_term',
            'shouldSeeReplies' => [
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                $this->searchTermTypeButton(SearchTermType::unknown),
                $this->removeButton($input),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type & known type' => [
            'searchTerm' => null,
            'input' => $this->getMessengerUserProfileUrlProvider()->getMessengerUserProfileUrl(Messenger::telegram, 'any_search_term'),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'next' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),

            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'help & empty search term' => [
            'searchTerm' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help & non-empty search term' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term',
                'search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'cancel & empty search term' => [
            'searchTerm' => null,
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider searchTermTypeStepDataProvider
     */
    public function testSearchTermTypeStep(
        SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerm,
            LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep
        );
    }

    public function searchTermTypeStepDataProvider(): Generator
    {
        yield 'type wrong' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'type not in the list' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => $this->searchTermTypeButton(SearchTermType::tiktok_username),
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'select type' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                ]),
            'input' => $this->searchTermTypeButton($searchTerm->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                ]),
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help' => [
            'searchTerm' => $searchTerm = (new SearchTermTransfer('any_search_term'))
                ->setTypes([
                    SearchTermType::instagram_username,
                    SearchTermType::telegram_username,
                    SearchTermType::organization_name,
                ]),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => new SearchTermTransfer('any_search_term'),
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider confirmStepDataProvider
     */
    public function testConfirmStep(
        SearchTermTransfer $searchTerm,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
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
            Fixtures::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3,
            Fixtures::SEARCH_TERM_INSTAGRAM_USERNAME_3,
            Fixtures::FEEDBACK_SEARCH_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            Fixtures::FEEDBACK_SEARCH_23_TELEGRAM_INSTAGRAM_USERNAME,
        ]);

        $conversation = $this->createConversation(
            LookupFeedbackTelegramBotConversation::class,
            (new LookupFeedbackTelegramBotConversationState())
                ->setSearchTerm($searchTerm)
                ->setStep(LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function confirmStepDataProvider(): Generator
    {
        yield 'unknown' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'yes & empty results' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'on_search',
                'empty_result',
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'yes & non-empty results' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer(
                Fixtures::INSTAGRAM_USERNAME_3,
                type: SearchTermType::instagram_username
            ),
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'on_search',
                $searchTerm->getText(),
                $this->searchTermTypeTrans($searchTerm->getType()),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'prev' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'help' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.confirm',
                'confirm',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param int $expectedSearchTermCountDelta
     * @param array $shouldSeeReplies
     * @return void
     * @dataProvider confirmStepSearchDataProvider
     */
    public function testConfirmStepSearch(
        SearchTermTransfer $searchTerm,
        int $expectedSearchTermCountDelta,
        array $shouldSeeReplies
    ): void
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
            Fixtures::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3,
            Fixtures::SEARCH_TERM_INSTAGRAM_USERNAME_3,
            Fixtures::FEEDBACK_SEARCH_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            Fixtures::FEEDBACK_SEARCH_23_TELEGRAM_INSTAGRAM_USERNAME,
            Fixtures::FEEDBACK_LOOKUP_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            Fixtures::FEEDBACK_LOOKUP_23_TELEGRAM_INSTAGRAM_USERNAME,
        ]);

        $feedbackSearchTermRepository = $this->getSearchTermRepository();
        $feedbackSearchTermPrevCount = $feedbackSearchTermRepository->count([]);
        $feedbackLookupRepository = $this->getFeedbackLookupRepository();
        $feedbackLookupPrevCount = $feedbackLookupRepository->count([]);

        $this->createConversation(
            LookupFeedbackTelegramBotConversation::class,
            (new LookupFeedbackTelegramBotConversationState())
                ->setSearchTerm($searchTerm)
                ->setStep(LookupFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED)
        );

        $this->typeText($this->yesButton());

        $this->assertEquals($feedbackSearchTermPrevCount + $expectedSearchTermCountDelta, $feedbackSearchTermRepository->count([]));
        $this->assertEquals($feedbackLookupPrevCount + 1, $feedbackLookupRepository->count([]));

        $feedbackLookup = $feedbackLookupRepository->findOneLast();
        $this->assertNotNull($feedbackLookup);

        $this->assertEquals($searchTerm->getText(), $feedbackLookup->getSearchTerm()->getText());
        $this->assertEquals($searchTerm->getType(), $feedbackLookup->getSearchTerm()->getType());
        $this->assertEquals(
            $searchTerm->getNormalizedText() ?? $searchTerm->getText(),
            $feedbackLookup->getSearchTerm()->getNormalizedText()
        );
        $this->assertEquals(
            $searchTerm->getMessengerUser()?->getId(),
            $feedbackLookup->getSearchTerm()->getMessengerUser()?->getIdentifier()
        );
        $this->assertEquals(
            $searchTerm->getMessengerUser()?->getMessenger(),
            $feedbackLookup->getSearchTerm()->getMessengerUser()?->getMessenger()
        );
        $this->assertEquals(
            $searchTerm->getMessengerUser()?->getUsername(),
            $feedbackLookup->getSearchTerm()->getMessengerUser()?->getUsername()
        );

        $this->shouldSeeReply(...$shouldSeeReplies);
    }

    public function confirmStepSearchDataProvider(): Generator
    {
        yield 'non-existing term & empty results' => [
            'searchTerm' => new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            'expectedSearchTermCountDelta' => 1,
            'shouldSeeReplies' => [
                'on_search',
                'empty_result',
                ...$this->chooseActionReplies(),
            ],
        ];

        yield 'existing term & non-empty results' => [
            'searchTerm' => $searchTerm = new SearchTermTransfer(Fixtures::INSTAGRAM_USERNAME_3, type: SearchTermType::instagram_username),
            'expectedSearchTermCountDelta' => 0,
            'shouldSeeReplies' => [
                'on_search',
                $searchTerm->getText(),
                $this->searchTermTypeTrans($searchTerm->getType()),
                ...$this->chooseActionReplies(),
            ],
        ];
    }

    protected function test(
        ?SearchTermTransfer $searchTerm,
        ?int $stateStep,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
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

        $conversation = $this->createConversation(
            LookupFeedbackTelegramBotConversation::class,
            (new LookupFeedbackTelegramBotConversationState())
                ->setSearchTerm($searchTerm)
                ->setStep($stateStep)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }
}
