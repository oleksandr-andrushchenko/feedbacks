<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Model\Feedback\Command\FeedbackCommandLimit;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\FeedbackSearchService;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Search\Searcher;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use DateTimeImmutable;
use Longman\TelegramBot\Entities\KeyboardButton;

/**
 * @property SearchFeedbackTelegramBotConversationState $state
 */
class SearchFeedbackTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;
    public const STEP_CONFIRM_QUERIED = 40;
    public const STEP_CREATE_CONFIRM_QUERIED = 50;
    public const STEP_CREATE_CONFIRMED = 60;

    public function __construct(
        private readonly Validator $validator,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackSearchCreator $feedbackSearchCreator,
        private readonly Searcher $searcher,
        private readonly FeedbackSearchService $feedbackSearchService,
        private readonly bool $searchTermTypeStep,
        private readonly bool $confirmStep,
        private readonly bool $createConfirmStep,
        private readonly array $searchProviders,
    )
    {
        parent::__construct(new SearchFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_CREATE_CONFIRM_QUERIED => $this->gotCreateConfirm($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->querySearchTerm($tg);
    }

    public function getStep(int $num): string
    {
        $originalNum = $num;
        $total = 2;

        if (!$this->confirmStep) {
            if ($originalNum > 1) {
                $num--;
            }

            $total--;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }

    public function getSearchTermQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.search_term', domain: 'search');
        $query = $tg->queryText($query);

        if (!$help) {
            $query .= $tg->queryTipText(
                rtrim($tg->view('search_term_types', context: ['types' => SearchTermType::base]))
                . "\n▫️ " . sprintf('<b>[ %s ]</b>', $tg->trans('query.search_term_put_one', domain: 'search'))
            );
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($searchTerm !== null) {
            $query .= $tg->alreadyAddedText('▫️ ' . $this->searchTermTelegramViewProvider->getSearchTermTelegramReverseView($searchTerm));
        }

        if ($help) {
            $query = $tg->view('search_search_term_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(true));
        }

        return $query;
    }

    public function getRemoveTermButton(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->removeButton($searchTerm->getNormalizedText() ?? $searchTerm->getText());
    }

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $message = $this->getSearchTermQuery($tg, $help);

        $buttons = [];

        $searchTerm = $this->state->getSearchTerm();

        if ($searchTerm !== null) {
            $buttons[] = $this->getRemoveTermButton($searchTerm, $tg);

            if ($this->confirmStep) {
                $buttons[] = $tg->nextButton();
            }
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'search');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function parseSearchTerm(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): void
    {
        $context = [
            'country_codes' => array_unique([
                $tg->getBot()->getEntity()->getCountryCode(),
                $tg->getCountryCode(),
            ]),
        ];

        if ($searchTerm->getType() === null) {
            $this->searchTermParser->parseWithGuessType($searchTerm, context: $context);
        } else {
            $this->searchTermParser->parseWithKnownType($searchTerm, context: $context);
        }
    }

    public function gotSearchTerm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->querySearchTerm($tg);
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($tg->matchInput($tg->nextButton()->getText()) && $searchTerm !== null) {
            if ($this->confirmStep) {
                return $this->queryConfirm($tg);
            }

            return $this->searchAndReply($tg, $entity);
        }

        if ($searchTerm !== null && $tg->matchInput($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->setSearchTerm(null);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySearchTerm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $searchTerm = new SearchTermTransfer($tg->getInput());

        $this->parseSearchTerm($searchTerm, $tg);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->replyWarning(implode("\n\n", [
                $tg->queryText($exception->getFirstMessage()),
                $tg->view('search_term_examples'),
            ]));

            return $this->querySearchTerm($tg);
        }

        $this->state->setSearchTerm($searchTerm);

        if ($searchTerm->getType() === null) {
            $types = $searchTerm->getTypes() ?? [];

            if (count($types) === 1) {
                $searchTerm->setType($types[0])->setTypes(null);
                $this->parseSearchTerm($searchTerm, $tg);
            } elseif ($this->searchTermTypeStep) {
                return $this->querySearchTermType($tg);
            } else {
                $searchTerm->setType(SearchTermType::unknown)->setTypes(null);
            }
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->state->getSearchTerm()->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTerm),
        ];
        $query = $tg->trans('query.search_term_type', parameters: $parameters, domain: 'search');
        $query = $tg->queryText($query);

        if ($help) {
            return $tg->view('search_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $message = $this->getSearchTermTypeQuery($tg, $help);

        $types = $this->getSearchTermTypes($this->state->getSearchTerm());

        $buttons = $this->getSearchTermTypeButtons($types, $tg);
        $buttons[] = $this->getRemoveTermButton($this->state->getSearchTerm(), $tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotSearchTermType(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->querySearchTermType($tg);
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($tg->matchInput($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->setSearchTerm(null);

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySearchTermType($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $type = $this->getSearchTermTypeByButton($tg->getInput(), $searchTerm, $tg);

        if ($type === null) {
            $tg->replyWrong(false);

            return $this->querySearchTermType($tg);
        }

        $original = $searchTerm->getTypes();
        $searchTerm->setType($type)->setTypes(null);

        $this->parseSearchTerm($searchTerm, $tg);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $searchTerm->setType(null)->setTypes($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->querySearchTerm($tg);
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    /**
     * @param SearchTermType[] $types
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSearchTermTypeButtons(array $types, TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (SearchTermType $type): KeyboardButton => $this->getSearchTermTypeButton($type, $tg), $types);
    }

    public function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    public function getSearchTermTypes(SearchTermTransfer $searchTerm): array
    {
        $types = $searchTerm->getTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types[] = SearchTermType::unknown;

        return $types;
    }

    public function getSearchTermTypeByButton(
        string $button,
        SearchTermTransfer $searchTerm,
        TelegramBotAwareHelper $tg
    ): ?SearchTermType
    {
        foreach ($this->getSearchTermTypes($searchTerm) as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $button) {
                return $type;
            }
        }

        return null;
    }

    public function getConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $searchTerm = $this->searchTermTelegramViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query .= $tg->trans('query.confirm', parameters: $parameters, domain: 'search');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('search_confirm_help', [
                'query' => $query,
                'search_term' => $searchTerm,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $message = $this->getConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton()];
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getLimitExceededReply(TelegramBotAwareHelper $tg, FeedbackCommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'search',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->feedbackSearchCreator->getOptions()->getLimits(),
        ]);
    }

    public function getCreateReply(TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($this->state->getSearchTerm()),
            'create_command' => $tg->command('create', html: true, link: true),
        ];
        $message = $tg->trans('reply.create', $parameters, domain: 'search');

        $message = $tg->okText($message);
        $message .= "\n";

        return $message;
    }

    public function searchAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state->getSearchTerm());
            $this->validator->validate($this->state);

            // todo: use command bus
            $feedbackSearch = $this->feedbackSearchCreator->createFeedbackSearch(
                new FeedbackSearchTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $this->state->getSearchTerm(),
                    $tg->getBot()->getEntity()
                )
            );

            $render = static fn (string $message) => $tg->reply($message);
            $context = [
                'bot' => $tg->getBot()->getEntity(),
                'countryCode' => $tg->getBot()->getEntity()->getCountryCode(),
                'full' => $tg->getBot()->getUser()?->getSubscriptionExpireAt() > new DateTimeImmutable(),
                'skipFirstItem' => true,
                'addCountry' => true,
                'addTime' => true,
            ];
            $providers = array_map(static fn (string $name): SearchProviderName => SearchProviderName::fromName($name), $this->searchProviders);
            array_unshift($providers, SearchProviderName::feedbacks);

            $searchTerm = $this->feedbackSearchService->getSearchTerm($feedbackSearch);
            $this->searcher->search($searchTerm, $render, $context, $providers);

            if ($this->createConfirmStep) {
                return $this->queryCreateConfirm($tg);
            }

            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);
            $message .= "\n";
            $message .= $this->getCreateReply($tg);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->querySearchTerm($tg);
        } catch (FeedbackCommandLimitExceededException $exception) {
            $message = $this->getLimitExceededReply($tg, $exception->getLimit());

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }
    }

    public function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryConfirm($tg, true);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        return $this->searchAndReply($tg, $entity);
    }

    public function getCreateConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $searchTerm = $this->searchTermTelegramViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $query = $tg->trans('query.create_confirm', parameters: $parameters, domain: 'search');
//        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('search_create_confirm_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryCreateConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CREATE_CONFIRM_QUERIED);

        $message = $this->getCreateConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getWillNotifyReply(TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($this->state->getSearchTerm()),
        ];
        $message = $tg->trans('reply.will_notify', $parameters, domain: 'search');

        $message = $tg->okText($tg->queryText($message));
        $message .= "\n";

        return $message;
    }

    public function gotCreateConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryCreateConfirm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryCreateConfirm($tg);
        }

        $this->state->setStep(self::STEP_CREATE_CONFIRMED);

        $tg->stopConversation($entity)->executeConversation(
            CreateFeedbackTelegramBotConversation::class,
            (new CreateFeedbackTelegramBotConversationState())
                ->setStep(CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED)
                ->setSearchTerms(new SearchTermsTransfer([$this->state->getSearchTerm()])),
            'queryRating'
        );

        return null;
    }
}