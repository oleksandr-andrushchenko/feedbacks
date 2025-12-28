<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Telegram\TelegramBotConversation;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackRatingProviderTrait;
use App\Tests\Traits\Feedback\FeedbackRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSearchTermTypeProviderTrait;
use App\Tests\Traits\Feedback\SearchTermRepositoryProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Tests\Traits\User\UserRepositoryProviderTrait;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Generator;

class CreateFeedbackTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use FeedbackRepositoryProviderTrait;
    use UserRepositoryProviderTrait;
    use FeedbackRatingProviderTrait;
    use FeedbackSearchTermTypeProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use SearchTermRepositoryProviderTrait;

    /**
     * @param string $input
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(string $input): void
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
                CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED
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

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'input' => $this->command('create'),
        ];

        yield 'input' => [
            'input' => FeedbackTelegramBotGroup::CREATE,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param array|null $shouldSeeSearchTerms
     * @return void
     * @dataProvider searchTermStepDataProvider
     */
    public function testSearchTermStep(
        ?array $searchTerms,
        ?Rating $rating,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?array $shouldSeeSearchTerms
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            null,
            CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            $shouldSeeSearchTerms
        );
    }

    public function searchTermStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'text.not_blank'],
            ['multiple lines', "first_line\nsecond_line", 'text.single_line'],
            ['forbidden chars', 'qweqwe; sdf', 'text.allowed_chars'],
            ['too short', 'q', 'text.min_length'],
            ['too long', str_repeat('q', 256 + 1), 'text.max_length'],
        ];

        foreach ($validators as [$key, $input, $reply]) {
            yield 'type ' . $key . ' & empty search terms' => [
                'searchTerms' => null,
                'rating' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeSearchTerms' => [],
            ];

            yield 'type ' . $key . ' & non-empty search terms' => [
                'searchTerms' => [
                    $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
                ],
                'rating' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.search_term',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($searchTerm->getText()),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeSearchTerms' => [
                    $searchTerm,
                ],
            ];
        }

        yield 'type & empty search term & unknown type' => [
            'searchTerms' => null,
            'rating' => null,
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                new SearchTermTransfer($input),
            ],
        ];

        yield 'type & empty search term & known type' => [
            'searchTerms' => null,
            'rating' => null,
            'input' => $input = $this->getMessengerUserProfileUrlProvider()
                ->getMessengerUserProfileUrl(Messenger::telegram, $username = 'any_search_term'),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($username),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                new SearchTermTransfer($input),
            ],
        ];

        yield 'type & single search term & known type' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'input' => $input = $this->getMessengerUserProfileUrlProvider()
                ->getMessengerUserProfileUrl(Messenger::telegram, $username = 'any_search_term'),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($username),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                new SearchTermTransfer($input),
            ],
        ];

        yield 'type & multiple search terms & known type' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::place_name),
            ],
            'rating' => null,
            'input' => $input = $this->getMessengerUserProfileUrlProvider()
                ->getMessengerUserProfileUrl(Messenger::telegram, $username = 'any_search_term'),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->removeButton($username),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                new SearchTermTransfer($input),
            ],
        ];

        yield 'remove & single search term' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [],
        ];

        yield 'remove & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::place_name),
            ],
            'rating' => null,
            'input' => $this->removeButton($searchTerm1->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm2,
            ],
        ];

        yield 'next & single search term & empty rating' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            ],
            'rating' => null,
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (Rating $rating): string => $this->ratingButton($rating), Rating::cases()),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'next & multiple single search terms & empty rating' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
            ],
            'rating' => null,
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (Rating $rating): string => $this->ratingButton($rating), Rating::cases()),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'help & empty search terms' => [
            'searchTerms' => null,
            'rating' => null,
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [],
        ];

        yield 'help & non-empty search terms' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            ],
            'rating' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.extra_search_term',
                'extra_search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('15613145672', type: SearchTermType::phone_number),
            ],
            'rating' => null,
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeSearchTerms' => null,
        ];

        foreach ($validators as [$key, $input, $reply]) {
            yield 'type ' . $key . ' & empty extra search terms' => [
                'searchTerms' => [
                    $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
                ],
                'rating' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.extra_search_term',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($searchTerm->getText()),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeSearchTerms' => [
                    $searchTerm,
                ],
            ];

            yield 'type ' . $key . ' & non-empty extra search terms' => [
                'searchTerms' => [
                    $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::organization_name),
                    $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::person_name),
                ],
                'rating' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.extra_search_term',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($searchTerm1->getText()),
                    $this->removeButton($searchTerm2->getText()),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
                'shouldSeeSearchTerms' => [
                    $searchTerm1,
                    $searchTerm2,
                ],
            ];
        }

        yield 'type & empty extra search terms & unknown type' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
            ],
            'rating' => null,
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
                new SearchTermTransfer($input),
            ],
        ];

        yield 'type & empty extra search terms & known type' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
            ],
            'rating' => null,
            'input' => $input = $this->getMessengerUserProfileUrlProvider()
                ->getMessengerUserProfileUrl(Messenger::telegram, $username = 'any_search_term'),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->removeButton($username),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
                new SearchTermTransfer($input),
            ],
        ];

        yield 'type & single extra search terms & known type' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::instagram_username),
            ],
            'rating' => null,
            'input' => $input = $this->getMessengerUserProfileUrlProvider()
                ->getMessengerUserProfileUrl(Messenger::telegram, $username = 'any_search_term'),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->removeButton($username),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                new SearchTermTransfer($input),
            ],
        ];

        yield 'remove & single extra search term' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
            ],
            'rating' => null,
            'input' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
            ],
        ];

        yield 'remove & multiple extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::onlyfans_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::place_name),
                $searchTerm3 = new SearchTermTransfer('any_search_term3', type: SearchTermType::organization_name),
            ],
            'rating' => null,
            'input' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm3->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm3,
            ],
        ];

        yield 'next & empty extra search terms & empty rating' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            ],
            'rating' => null,
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (Rating $rating): string => $this->ratingButton($rating), Rating::cases()),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'next & empty extra search terms & non-empty rating' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            ],
            'rating' => $rating = Rating::random(),
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    Rating::cases()
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'help & empty extra search terms' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::person_name),
            ],
            'rating' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.extra_search_term',
                'extra_search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'help & non-empty extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::organization_name),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::person_name),
            ],
            'rating' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.extra_search_term',
                'extra_search_term',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'cancel & empty extra search terms' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::organization_name),
            ],
            'rating' => null,
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param SearchTermTransfer[]|null $shouldSeeSearchTerms
     * @return void
     * @dataProvider searchTermTypeStepDataProvider
     */
    public function testSearchTermTypeStep(
        ?array $searchTerms,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?array $shouldSeeSearchTerms = null
    ): void
    {
        $this->test(
            $searchTerms,
            null,
            null,
            CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            $shouldSeeSearchTerms
        );
    }

    public function searchTermTypeStepDataProvider(): Generator
    {
        yield 'type wrong & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'input' => 'kjlk',
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'type not in the list & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'select type & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'input' => $this->searchTermTypeButton($searchTerm->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'select type & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'input' => $this->searchTermTypeButton($searchTerm2->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'remove & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'input' => $this->removeButton($searchTerm->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [],
        ];

        yield 'remove & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term1', type: SearchTermType::tiktok_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'input' => $this->removeButton($searchTerm3->getText()),
            'shouldSeeReplies' => [
                'query.search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'help & single search term' => [
            'searchTerms' => [
                $searchTerm = (new SearchTermTransfer('any_search_term'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm,
            ],
        ];

        yield 'help & multiple search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term1', type: SearchTermType::tiktok_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm2->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm2->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term'),
            ],
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


        yield 'type wrong & multiple extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                $searchTerm3,
            ],
        ];

        yield 'type not in the list & multiple extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'input' => $this->searchTermTypeButton(SearchTermType::telegram_username),
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.search_term_type',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                $searchTerm3,
            ],
        ];

        yield 'select type & single extra search term' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::telegram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                    ]),
            ],
            'input' => $this->searchTermTypeButton($searchTerm2->getTypes()[0]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'select type & multiple extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::youtube_username,
                        SearchTermType::tiktok_username,
                    ]),
            ],
            'input' => $this->searchTermTypeButton($searchTerm3->getTypes()[count($searchTerm3->getTypes()) - 1]),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermTransfer $searchTerm): string => $this->removeButton($searchTerm->getText()), [$searchTerm1, $searchTerm2, $searchTerm3]),
                ...[
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                $searchTerm3,
            ],
        ];

        yield 'remove & single extra search term' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::telegram_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'input' => $this->removeButton($searchTerm2->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
            ],
        ];

        yield 'remove & multiple extra search term' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'input' => $this->removeButton($searchTerm3->getText()),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm1->getText()),
                $this->removeButton($searchTerm2->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'help & single extra search term' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = (new SearchTermTransfer('any_search_term2'))
                    ->setTypes([
                        SearchTermType::instagram_username,
                        SearchTermType::telegram_username,
                        SearchTermType::organization_name,
                    ]),
            ],
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm2->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm1->getText()),
                    $this->removeButton($searchTerm2->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
            ],
        ];

        yield 'help & multiple extra search terms' => [
            'searchTerms' => [
                $searchTerm1 = new SearchTermTransfer('any_search_term', type: SearchTermType::instagram_username),
                $searchTerm2 = new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                $searchTerm3 = (new SearchTermTransfer('any_search_term3'))
                    ->setTypes([
                        SearchTermType::tiktok_username,
                        SearchTermType::youtube_username,
                    ]),
            ],
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.search_term_type',
                'search_term_type',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermType $type): string => $this->searchTermTypeButton($type), $searchTerm3->getTypes()),
                ...[
                    $this->searchTermTypeButton(SearchTermType::unknown),
                    $this->removeButton($searchTerm3->getText()),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_TYPE_QUERIED,
            'shouldSeeSearchTerms' => [
                $searchTerm1,
                $searchTerm2,
                $searchTerm3,
            ],
        ];

        yield 'cancel & empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::car_number),
            ],
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

        yield 'cancel & non-empty extra search terms' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::car_number),
                new SearchTermTransfer('1563145672', type: SearchTermType::phone_number),
                new SearchTermTransfer('any_search_term3', type: SearchTermType::telegram_username),
            ],
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
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider ratingStepDataProvider
     */
    public function testRatingStep(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function ratingStepDataProvider(): Generator
    {
        yield 'type wrong & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => null,
            'description' => null,
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (Rating $rating): string => $this->ratingButton($rating), Rating::cases()),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'type wrong & non-empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => null,
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    Rating::cases()
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'prev & empty extra search terms' => [
            'searchTerms' => [
                $searchTerm = new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($searchTerm->getText()),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'prev & non-empty extra search terms' => [
            'searchTerms' => $searchTerms = [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
                new SearchTermTransfer('any_search_term2', type: SearchTermType::telegram_username),
                new SearchTermTransfer('any_search_term3', type: SearchTermType::vkontakte_username),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.extra_search_term',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (SearchTermTransfer $searchTerm): string => $this->removeButton($searchTerm->getText()), $searchTerms),
                ...[
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED,
        ];

        yield 'next & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'next & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'input' => $this->nextButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.rating',
                'rating',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (Rating $rating): string => $this->ratingButton($rating), Rating::cases()),
                ...[
                    $this->prevButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'help & non-empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => 'any_description',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.rating',
                'rating',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    Rating::cases()
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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

        yield 'type & empty rating' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => null,
            'description' => null,
            'input' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type same & non-empty rating & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::neutral,
            'description' => null,
            'input' => $this->selectedText($this->ratingButton($rating)),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type same & non-empty rating & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::neutral,
            'description' => $description = 'any description',
            'input' => $this->selectedText($this->ratingButton($rating)),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type different & non-empty rating & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::satisfied,
            'description' => null,
            'input' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'type different & non-empty rating & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::satisfied,
            'description' => $description = 'any description',
            'input' => $this->ratingButton(Rating::neutral),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];
    }

    /**
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider descriptionStepDataProvider
     */
    public function testDescriptionStep(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function descriptionStepDataProvider(): Generator
    {
        $validators = [
            ['empty', '', 'description.not_blank'],
            ['too short', 'q', 'description.min_length'],
            ['too long', str_repeat('q', 2048 + 1), 'description.max_length'],
        ];

        foreach ($validators as [$key, $input, $reply]) {
            yield 'type ' . $key . ' & empty description' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
                ],
                'rating' => Rating::random(),
                'description' => null,
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.description',
                ],
                'shouldSeeButtons' => [
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            ];

            yield 'type ' . $key . ' & non-empty description' => [
                'searchTerms' => [
                    new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
                ],
                'rating' => Rating::random(),
                'description' => $description = 'any description',
                'input' => $input,
                'shouldSeeReplies' => [
                    $reply,
                    'query.description',
                ],
                'shouldSeeButtons' => [
                    $this->removeButton($description),
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
                'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
            ];
        }

        yield 'prev' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => $rating = Rating::random(),
            'description' => 'any_description',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.rating',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (Rating $r): string => $rating === $r ? $this->selectedText($this->ratingButton($r)) : $this->ratingButton($r),
                    Rating::cases()
                ),
                ...[
                    $this->prevButton(),
                    $this->nextButton(),
                    $this->helpButton(),
                    $this->cancelButton(),
                ],
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED,
        ];

        yield 'next' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'help & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.description',
                'description',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.description',
                'description',
                'help.use_input',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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

        yield 'type' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => 'any_description2',
            'shouldSeeReplies' => [
                'query.confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'remove' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'input' => $this->removeButton($description),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param Rating $rating
     * @param string|null $description
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider confirmStepDataProvider
     */
    public function testConfirmStep(
        array $searchTerms,
        Rating $rating,
        ?string $description,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->test(
            $searchTerms,
            $rating,
            $description,
            CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
            null
        );
    }

    public function confirmStepDataProvider(): Generator
    {
        yield 'type wrong' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'yes' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.created',
                'query.send_to_channel_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'prev & empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'prev & non-empty description' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => $description = 'any description',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.description',
            ],
            'shouldSeeButtons' => [
                $this->removeButton($description),
                $this->prevButton(),
                $this->nextButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_DESCRIPTION_QUERIED,
        ];

        yield 'help' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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
     * @param SearchTermTransfer[] $searchTerms
     * @param Rating $rating
     * @param string|null $description
     * @param int $expectedSearchTermCountDelta
     * @return void
     * @dataProvider confirmStepCreateDataProvider
     */
    public function testConfirmStepCreate(
        array $searchTerms,
        Rating $rating,
        ?string $description,
        int $expectedSearchTermCountDelta
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
            Fixtures::FEEDBACK_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            Fixtures::FEEDBACK_23_TELEGRAM_INSTAGRAM_USERNAME,
        ]);

        $feedbackSearchTermRepository = $this->getSearchTermRepository();
        $feedbackSearchTermPrevCount = $feedbackSearchTermRepository->count([]);
        $feedbackRepository = $this->getFeedbackRepository();
        $feedbackPrevCount = $feedbackRepository->count([]);

        $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setSearchTerms(new SearchTermsTransfer($searchTerms))
                ->setRating($rating)
                ->setDescription($description)
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_CONFIRM_QUERIED)
        );

        $this->typeText($this->yesButton());

        $this->assertEquals($feedbackSearchTermPrevCount + $expectedSearchTermCountDelta, $feedbackSearchTermRepository->count([]));
        $this->assertEquals($feedbackPrevCount + 1, $feedbackRepository->count([]));

        $feedback = $feedbackRepository->findOneLast();
        $this->assertNotNull($feedback);

        $searchTerms = $feedback->getSearchTerms()->toArray();
        usort($searchTerms, static fn (SearchTerm $a, SearchTerm $b): int => $a->getId() <=> $b->getId());

        foreach ($searchTerms as $index => $searchTerm) {
            $this->assertEquals($searchTerms[$index]->getText(), $searchTerm->getText());
            $this->assertEquals($searchTerms[$index]->getType(), $searchTerm->getType());
            $this->assertEquals(
                $searchTerms[$index]->getNormalizedText() ?? $searchTerms[$index]->getText(),
                $searchTerm->getNormalizedText()
            );
            $this->assertEquals(
                $searchTerms[$index]->getMessengerUser()?->getId(),
                $searchTerm->getMessengerUser()?->getIdentifier()
            );
            $this->assertEquals(
                $searchTerms[$index]->getMessengerUser()?->getMessenger(),
                $searchTerm->getMessengerUser()?->getMessenger()
            );
            $this->assertEquals(
                $searchTerms[$index]->getMessengerUser()?->getUsername(),
                $searchTerm->getMessengerUser()?->getUsername()
            );
        }

        $this->assertEquals($rating, $feedback->getRating());
        $this->assertEquals($description, $feedback->getText());

        $this->shouldSeeReply('reply.created');
    }

    public function confirmStepCreateDataProvider(): Generator
    {
        yield 'single term & non-existing term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'expectedSearchTermCountDelta' => 1,
        ];

        yield 'multiple terms & existing term' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
                new SearchTermTransfer(
                    $this->getMessengerUserProfileUrlProvider()->getMessengerUserProfileUrl(
                        Messenger::instagram,
                        $username = Fixtures::INSTAGRAM_USERNAME_3
                    ),
                    type: SearchTermType::instagram_username,
                    normalizedText: $username
                ),
            ],
            'rating' => Rating::random(),
            'description' => null,
            'expectedSearchTermCountDelta' => 1,
        ];
    }

    /**
     * @param SearchTermTransfer[] $searchTerms
     * @param Rating $rating
     * @param string|null $description
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider sendToChannelConfirmStepDataProvider
     */
    public function testSendToChannelConfirmStep(
        array $searchTerms,
        Rating $rating,
        ?string $description,
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
            Fixtures::FEEDBACK_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            Fixtures::FEEDBACK_23_TELEGRAM_INSTAGRAM_USERNAME,
        ]);

        $conversation = $this->createConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setSearchTerms(new SearchTermsTransfer($searchTerms))
                ->setRating($rating)
                ->setDescription($description)
                ->setCreatedId('feedback1')
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function sendToChannelConfirmStepDataProvider(): Generator
    {
        yield 'type wrong' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => 'unknown',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.send_to_channel_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'yes' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'reply.will_sent_to_channel',
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'no' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->noButton(),
            'shouldSeeReplies' => [
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'help' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.send_to_channel_confirm',
                'send_to_channel_confirm',
                'help.use_keyboard',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CreateFeedbackTelegramBotConversation::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'searchTerms' => [
                new SearchTermTransfer('any_search_term', type: SearchTermType::unknown),
            ],
            'rating' => Rating::random(),
            'description' => 'any_description',
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
     * @param SearchTermTransfer[]|null $searchTerms
     * @param Rating|null $rating
     * @param string|null $description
     * @param int|null $stateStep
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param array|null $shouldSeeSearchTerms
     * @return void
     */
    protected function test(
        ?array $searchTerms,
        ?Rating $rating,
        ?string $description,
        ?int $stateStep,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?array $shouldSeeSearchTerms
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
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setSearchTerms(new SearchTermsTransfer($searchTerms))
                ->setRating($rating)
                ->setDescription($description)
                ->setStep($stateStep)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeSearchTerms($conversation, $shouldSeeSearchTerms)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    /**
     * @param TelegramBotConversation $conversation
     * @param SearchTermTransfer[]|null $shouldSeeSearchTerms
     * @return $this
     */
    protected function shouldSeeSearchTerms(TelegramBotConversation $conversation, ?array $shouldSeeSearchTerms): static
    {
        if ($shouldSeeSearchTerms !== null) {
            $searchTerms = $conversation->getState()['search_terms'] ?? [];

            $this->assertCount(count($shouldSeeSearchTerms), $searchTerms);

            foreach ($shouldSeeSearchTerms as $index => $shouldSeeSearchTerm) {
                $this->assertEquals($shouldSeeSearchTerm->getText(), $searchTerms[$index]['text']);
            }
        }

        return $this;
    }
}
