<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
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
class SearchFeedbackTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_SEARCH_TERM_QUERIED = 10;
    public const int STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const int STEP_CANCEL_PRESSED = 30;
    public const int STEP_CONFIRM_QUERIED = 40;
    public const int STEP_CREATE_CONFIRM_QUERIED = 50;
    public const int STEP_CREATE_CONFIRMED = 60;

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

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->querySearchTerm($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_CREATE_CONFIRM_QUERIED => $this->gotCreateConfirm($tg, $entity),
        };
    }

    private function getStep(int $num): string
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

    private function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $message = $this->getStep(1);
        $message .= $tg->trans('query.search_term', domain: 'search');
        $message = $tg->queryText($message);

        if (!$help) {
            $message .= $tg->queryTipText(
                rtrim($tg->view('search_term_types', context: ['types' => SearchTermType::base]))
                . "\n▫️ " . sprintf('<b>[ %s ]</b>', $tg->trans('query.search_term_put_one', domain: 'search'))
            );
        }

        $searchTerm = $this->state->getSearchTerm();

        if ($searchTerm !== null) {
            $message .= $tg->alreadyAddedText('▫️ ' . $this->searchTermTelegramViewProvider->getSearchTermTelegramReverseView($searchTerm));
        }

        if ($help) {
            $message = $tg->view('search_search_term_help', [
                'query' => $message,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(true));
        }

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

    private function getRemoveTermButton(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->removeButton($searchTerm->getNormalizedText() ?? $searchTerm->getText());
    }

    private function gotSearchTerm(TelegramBotAwareHelper $tg, Entity $entity): null
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

        $searchTerm = new SearchTermTransfer($tg->getText()->getRawValue());

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

    private function queryConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $message = $this->getStep(2);
        $searchTerm = $this->searchTermTelegramViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $message .= $tg->trans('query.confirm', parameters: $parameters, domain: 'search');
        $message = $tg->queryText($message);

        if ($help) {
            $message = $tg->view('search_confirm_help', [
                'query' => $message,
                'search_term' => $searchTerm,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(false));
        }

        $buttons = [];
        $buttons[] = [$tg->yesButton()];
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function searchAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
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
//                'skipFirstItem' => true,
                'addCountry' => true,
                'addTime' => true,
            ];
            $providers = array_map(static fn (string $name): SearchProviderName => SearchProviderName::fromName($name), $this->searchProviders);
            array_unshift($providers, SearchProviderName::feedbacks);

            $searchTerm = $this->feedbackSearchService->getSearchTerm($feedbackSearch);
            $this->searcher->search([$searchTerm], $render, $context, $providers);

            if ($this->createConfirmStep) {
                return $this->queryCreateConfirm($tg);
            }

            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);
            $message .= "\n";
            $parameters = [
                'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($this->state->getSearchTerm()),
                'create_command' => $tg->command('create', html: true, link: true),
            ];
            $message .= $tg->trans('reply.create', $parameters, domain: 'search');

            $message .= $tg->okText($message);
            $message .= "\n";

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->querySearchTerm($tg);
        } catch (FeedbackCommandLimitExceededException $exception) {
            $message = $tg->view('command_limit_exceeded', [
                'command' => 'search',
                'period' => $exception->getLimit()->getPeriod(),
                'count' => $exception->getLimit()->getCount(),
                'limits' => $this->feedbackSearchCreator->getOptions()->getLimits(),
            ]);

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }
    }

    private function queryCreateConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CREATE_CONFIRM_QUERIED);

        $searchTerm = $this->searchTermTelegramViewProvider->getSearchTermTelegramView($this->state->getSearchTerm());
        $parameters = [
            'search_term' => $searchTerm,
        ];
        $message = $tg->trans('query.create_confirm', parameters: $parameters, domain: 'search');
//        $message = $tg->queryText($message);

        if ($help) {
            $message = $tg->view('search_create_confirm_help', [
                'query' => $message,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(false));
        }

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getWillNotifyReply(TelegramBotAwareHelper $tg): string
    {
        $parameters = [
            'search_term' => $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($this->state->getSearchTerm()),
        ];
        $message = $tg->trans('reply.will_notify', $parameters, domain: 'search');

        $message = $tg->okText($tg->queryText($message));
        $message .= "\n";

        return $message;
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'search');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    private function parseSearchTerm(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): void
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

    private function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $searchTerm = $this->state->getSearchTerm()->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTerm),
        ];
        $message = $tg->trans('query.search_term_type', parameters: $parameters, domain: 'search');
        $message = $tg->queryText($message);

        if ($help) {
            return $tg->view('search_search_term_type_help', [
                'query' => $message,
                'search_term' => $searchTerm,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(false));
        }

        $types = $this->getSearchTermTypes($this->state->getSearchTerm());

        $buttons = array_map(fn (SearchTermType $type): KeyboardButton => $this->getSearchTermTypeButton($type, $tg), $types);
        $buttons[] = $this->getRemoveTermButton($this->state->getSearchTerm(), $tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getSearchTermTypes(SearchTermTransfer $searchTerm): array
    {
        $types = $searchTerm->getTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types[] = SearchTermType::unknown;

        return $types;
    }

    private function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    private function gotSearchTermType(TelegramBotAwareHelper $tg, Entity $entity): null
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

        $type = null;
        foreach ($this->getSearchTermTypes($searchTerm) as $type) {
            if ($this->getSearchTermTypeButton($type, $tg)->getText() === $tg->getText()->getRawValue()) {
                break;
            }
        }

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

    private function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
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

    private function gotCreateConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
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