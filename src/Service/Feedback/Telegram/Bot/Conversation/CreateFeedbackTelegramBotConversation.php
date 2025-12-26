<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Entity\Telegram\TelegramChannel;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Model\Feedback\Command\FeedbackCommandLimit;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Search\Viewer\Telegram\FeedbackTelegramSearchViewer;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use App\Service\Telegram\Channel\View\TelegramChannelLinkViewProvider;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * /**
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_SEARCH_TERM_QUERIED = 10;
    public const STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;
    public const STEP_RATING_QUERIED = 40;
    public const STEP_DESCRIPTION_QUERIED = 50;
    public const STEP_CONFIRM_QUERIED = 60;
    public const STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED = 70;

    public function __construct(
        private readonly Validator $validator,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly FeedbackCreator $feedbackCreator,
        private readonly FeedbackRatingProvider $feedbackRatingProvider,
        private readonly MessageBusInterface $eventBus,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly FeedbackTelegramSearchViewer $feedbackTelegramSearchViewer,
        private readonly TelegramChannelMatchesProvider $telegramChannelMatchesProvider,
        private readonly TelegramChannelLinkViewProvider $telegramChannelLinkViewProvider,
        private readonly LoggerInterface $logger,
        private readonly bool $searchTermTypeStep,
        private readonly bool $extraSearchTermStep,
        private readonly bool $descriptionStep,
        private readonly bool $confirmStep,
        private readonly bool $sendToChannelConfirmStep,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_RATING_QUERIED => $this->gotRating($tg, $entity),
            self::STEP_DESCRIPTION_QUERIED => $this->gotDescription($tg, $entity),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
            self::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED => $this->gotSendToChannelConfirm($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->querySearchTerm($tg);
    }

    public function getStep(int $num, string $symbols = ''): string
    {
        $originalNum = $num;
        $total = 5;

        if (!$this->descriptionStep) {
            if ($originalNum > 2) {
                $num--;
            }

            $total--;
        }

        if (!$this->confirmStep) {
            if ($originalNum > 3) {
                $num--;
            }

            $total--;
        }

        if (!$this->sendToChannelConfirmStep) {
            if ($originalNum > 4) {
                $num--;
            }

            $total--;
        }

        return sprintf('[%d/%d%s] ', $num, $total, $symbols);
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'create');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function getSearchTermQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $searchTerms = $this->state->getSearchTerms();

        if ($searchTerms->hasItems()) {
            $query = $this->getStep(1, '**');
            $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
                $searchTerms,
                forceType: false
            );
            $parameters = [
                'search_term' => $searchTermView,
            ];
            $query .= $tg->trans('query.extra_search_term', parameters: $parameters, domain: 'create');
            $query = $tg->queryText($query, true);
        } else {
            $searchTermView = null;
            $query = $this->getStep(1);
            $query .= $tg->trans('query.search_term', domain: 'create');
            $query = $tg->queryText($query);
        }

        if (!$help) {
            $skipTypes = [];

            foreach ($searchTerms->getItemsAsArray() as $searchTerm) {
                $skipTypes[] = $searchTerm->getType();

                if (in_array($searchTerm->getType(), SearchTermType::messengers, true)) {
                    $skipTypes[] = SearchTermType::messenger_username;
                    $skipTypes[] = SearchTermType::messenger_profile_url;
                }
            }

            $types = array_filter(
                SearchTermType::base,
                static fn (SearchTermType $type): bool => !in_array($type, $skipTypes, true)
            );
            $query .= $tg->queryTipText(
                rtrim($tg->view('search_term_types', context: ['types' => $types]))
                . "\n▫️ " . sprintf('<b>[ %s ]</b>', $tg->trans('query.search_term_put_one', domain: 'create'))
            );
        }

        if ($searchTerms->hasItems()) {
            $query .= $tg->alreadyAddedText(implode("\n", array_map(
                fn (SearchTermTransfer $searchTerm): string => '▫️ ' . $this->searchTermTelegramViewProvider
                        ->getSearchTermTelegramReverseView($searchTerm),
                $searchTerms->getItems()
            )));
        }

        if ($help) {
            if ($searchTerms->hasItems()) {
                $query = $tg->view('create_extra_search_term_help', [
                    'query' => $query,
                    'search_term' => $searchTermView,
                ]);
            } else {
                $query = $tg->view('create_search_term_help', [
                    'query' => $query,
                ]);
            }
        } else {
            $query .= $tg->queryTipText($tg->useText(true));
        }

        return $query;
    }

    public function getRemoveTermButton(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->removeButton($searchTerm->getNormalizedText() ?? $searchTerm->getText());
    }

    /**
     * @param SearchTermsTransfer $searchTerms
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getRemoveTermButtons(SearchTermsTransfer $searchTerms, TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (SearchTermTransfer $searchTerm): KeyboardButton => $this->getRemoveTermButton($searchTerm, $tg),
            $searchTerms->getItems()
        );
    }

    public function getTermByRemoveTermButton(
        string $button,
        SearchTermsTransfer $searchTerms,
        TelegramBotAwareHelper $tg
    ): ?SearchTermTransfer
    {
        foreach ($searchTerms->getItems() as $searchTerm) {
            if ($this->getRemoveTermButton($searchTerm, $tg)->getText() === $button) {
                return $searchTerm;
            }
        }

        return null;
    }

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $searchTerms = $this->state->getSearchTerms();
        $message = $this->getSearchTermQuery($tg, $help);

        $buttons = [];

        if ($searchTerms->hasItems()) {
            $buttons[] = $this->getRemoveTermButtons($searchTerms, $tg);
            $buttons[] = $tg->nextButton();
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
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

        $searchTerms = $this->state->getSearchTerms();

        if ($searchTerms->hasItems()) {
            if ($tg->matchInput($tg->nextButton()->getText())) {
                return $this->queryRating($tg, $entity);
            }

            $searchTerm = $this->getTermByRemoveTermButton($tg->getInput(), $searchTerms, $tg);

            if ($searchTerm !== null) {
                $this->state->getSearchTerms()->removeItem($searchTerm);

                return $this->querySearchTerm($tg);
            }
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

        $this->state->getSearchTerms()->addItem($searchTerm);

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

        if ($this->extraSearchTermStep) {
            return $this->querySearchTerm($tg);
        }

        return $this->queryRating($tg, $entity);
    }

    public function getSearchTermTypeQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1, '*');
        $searchTerm = $this->state->getSearchTerms()->getLastItem();
        $searchTermView = $searchTerm->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTermView),
        ];
        $query .= $tg->trans('query.search_term_type', parameters: $parameters, domain: 'create');
        $query = $tg->queryText($query);

        if ($help) {
            return $tg->view('create_search_term_type_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        }

        $query .= $tg->queryTipText($tg->useText(false));

        return $query;
    }

    public function getSearchTermTypes(SearchTermTransfer $searchTerm): array
    {
        $types = $searchTerm->getTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types[] = SearchTermType::unknown;

        return $types;
    }

    public function querySearchTermType(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_TYPE_QUERIED);

        $searchTerm = $this->state->getSearchTerms()->getLastItem();
        $message = $this->getSearchTermTypeQuery($tg, $help);

        $buttons = $this->getSearchTermTypeButtons($searchTerm, $tg);
        $buttons[] = $this->getRemoveTermButton($searchTerm, $tg);
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

        $searchTerm = $this->state->getSearchTerms()->getLastItem();

        if ($tg->matchInput($this->getRemoveTermButton($searchTerm, $tg)->getText())) {
            $this->state->getSearchTerms()->removeItem($searchTerm);

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

            return $this->querySearchTermType($tg);
        }

        if ($this->extraSearchTermStep) {
            return $this->querySearchTerm($tg);
        }

        return $this->queryRating($tg, $entity);
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSearchTermTypeButtons(SearchTermTransfer $searchTerm, TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (SearchTermType $type): KeyboardButton => $this->getSearchTermTypeButton($type, $tg),
            $this->getSearchTermTypes($searchTerm)
        );
    }

    public function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    /**
     * @param string $button
     * @param SearchTermTransfer $searchTerm
     * @param TelegramBotAwareHelper $tg
     * @return SearchTermType|null
     */
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

    public function getRatingQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $query .= $tg->trans('query.rating', $parameters, domain: 'create');
        $query = $tg->queryText($query);

        if ($this->state->getRating() !== null) {
            $query .= $tg->alreadyAddedText($this->feedbackRatingProvider->getRatingComposeName($this->state->getRating()));
        }

        if ($help) {
            $query = $tg->view('create_rating_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getRatingButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (Rating $rating): KeyboardButton => $this->getRatingButton($rating, $tg), Rating::cases());
    }

    public function getRatingButton(Rating $rating, TelegramBotAwareHelper $tg): KeyboardButton
    {
        $name = $this->feedbackRatingProvider->getRatingComposeName($rating);

        if ($rating === $this->state->getRating()) {
            $name = $tg->selectedText($name);
        }

        return $tg->button($name);
    }

    public function getRatingByButton(string $button, TelegramBotAwareHelper $tg): ?Rating
    {
        foreach (Rating::cases() as $rating) {
            if ($this->getRatingButton($rating, $tg)->getText() === $button) {
                return $rating;
            }
        }

        return null;
    }

    public function queryRating(TelegramBotAwareHelper $tg, Entity $entity, bool $help = false): null
    {
        $this->state->setStep(self::STEP_RATING_QUERIED);

        $message = $this->getRatingQuery($tg, $help);

        $buttons = [];
        $buttons[] = $this->getRatingButtons($tg);

        if ($this->state->getRating() === null) {
            $buttons[] = $tg->prevButton();
        } else {
            $buttons[] = [$tg->prevButton(), $tg->nextButton()];
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotRating(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->queryRating($tg, $entity);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            if ($this->extraSearchTermStep) {
                return $this->querySearchTerm($tg);
            }

            return $this->querySearchTerm($tg);
        }

        if ($tg->matchInput($tg->nextButton()->getText()) && $this->state->getRating() !== null) {
            return $this->queryDescription($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryRating($tg, $entity, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $rating = $this->getRatingByButton($tg->getInput(), $tg);

        if ($rating === null) {
            $tg->replyWrong(false);

            return $this->queryRating($tg, $entity);
        }

        $original = $this->state->getRating();
        $this->state->setRating($rating);

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->state->setRating($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryRating($tg, $entity);
        }

        if ($this->descriptionStep) {
            return $this->queryDescription($tg);
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function getDescriptionQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(3);
        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $query .= $tg->trans('query.description', $parameters, domain: 'create');
        $query = $tg->queryText($query, true);

        if (!$help) {
            $query .= $tg->queryTipText($tg->trans('query.description_tip', domain: 'create'));
        }

        if ($this->state->getDescription() !== null) {
            $query .= $tg->alreadyAddedText($this->state->getDescription());
        }

        if ($help) {
            $query = $tg->view('create_description_help', [
                'query' => $query,
                'search_term' => $searchTermView,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(true));
        }

        return $query;
    }

    public function getCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('✅ ' . $tg->trans('keyboard.create_confirm', domain: 'create'));
    }

    public function queryDescription(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DESCRIPTION_QUERIED);

        $message = $this->getDescriptionQuery($tg, $help);

        $buttons = [];

        if ($this->state->getDescription() !== null) {
            $buttons[] = $tg->removeButton($this->state->getDescription());
        }

        if ($this->confirmStep) {
            $buttons[] = [$tg->prevButton(), $tg->nextButton()];
        } else {
            $buttons[] = $this->getCreateConfirmButton($tg);
            $buttons[] = $tg->prevButton();
        }


        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotDescription(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDescription($tg);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryRating($tg, $entity);
        }

        if ($this->confirmStep) {
            if ($tg->matchInput($tg->nextButton()->getText())) {
                return $this->queryConfirm($tg);
            }
        } else {
            if ($tg->matchInput($this->getCreateConfirmButton($tg)->getText())) {
                return $this->createAndReply($tg, $entity);
            }
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryDescription($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($this->state->getDescription() !== null) {
            if ($tg->matchInput($tg->removeButton($this->state->getDescription())->getText())) {
                $this->state->setDescription(null);

                return $this->queryDescription($tg);
            }
        }

        $original = $this->state->getDescription();
        $this->state->setDescription($tg->getInput());

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->state->setDescription($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDescription($tg);
        }

        if ($this->confirmStep) {
            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function getConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(4);

        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $query .= $tg->trans('query.confirm_preview', parameters: $parameters, domain: 'create');
        $query .= ":";
        $query = $tg->queryText($query);
        $query .= "\n\n";
        $query .= $this->feedbackTelegramSearchViewer->getFeedbackTelegramView(
            $tg->getBot()->getEntity(),
            $this->feedbackCreator->constructFeedback($this->constructTransfer($tg)),
            addCountry: true,
            addTime: true,
            addQuotes: true,
            locale: $tg->getBot()->getEntity()->getLocaleCode()
        );
        $query .= "\n\n";
        $query .= $tg->queryText($tg->trans('query.confirm', parameters: $parameters, domain: 'create'));

        if ($help) {
            $query = $tg->view('create_confirm_help', [
                'query' => $query,
                'search_term' => $searchTermView,
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
        $buttons[] = $tg->yesButton();
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getLimitExceededReply(TelegramBotAwareHelper $tg, FeedbackCommandLimit $limit): string
    {
        return $tg->view('command_limit_exceeded', [
            'command' => 'create',
            'period' => $limit->getPeriod(),
            'count' => $limit->getCount(),
            'limits' => $this->feedbackCreator->getOptions()->getLimits(),
        ]);
    }

    public function getSendToChannelConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(5);
        $channels = $this->telegramChannelMatchesProvider->getTelegramChannelMatches(
            $tg->getBot()->getUser(),
            $tg->getBot()->getEntity()
        );
        $channelNamesView = implode(
            ', ',
            array_map(
                fn (TelegramChannel $channel): string => $this->telegramChannelLinkViewProvider
                    ->getTelegramChannelLinkView($channel, html: true),
                $channels
            )
        );

        $parameters = [
            'channels' => $channelNamesView,
        ];
        $query .= $tg->trans('query.send_to_channel_confirm', parameters: $parameters, domain: 'create');
        $query = $tg->queryText($query);

        if ($help) {
            $feedback = $this->feedbackRepository->find($this->state->getCreatedId());
            $feedbackView = $this->feedbackTelegramSearchViewer->getFeedbackTelegramView(
                $tg->getBot()->getEntity(),
                $feedback,
                addCountry: true,
                addTime: true,
                locale: $tg->getBot()->getEntity()->getLocaleCode()
            );

            $query = $tg->view('create_send_to_channel_confirm_help', [
                'query' => $query,
                'channels' => $channelNamesView,
                'feedback' => $feedbackView,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function querySendToChannelConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED);

        $message = $this->getSendToChannelConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function constructTransfer(TelegramBotAwareHelper $tg): FeedbackTransfer
    {
        return new FeedbackTransfer(
            messengerUser: $tg->getBot()->getMessengerUser(),
            searchTerms: $this->state->getSearchTerms(),
            rating: $this->state->getRating(),
            description: $this->state->getDescription(),
            telegramBot: $tg->getBot()->getEntity()
        );
    }

    public function createAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state);

            // todo: use command bus
            $feedback = $this->feedbackCreator->createFeedback($this->constructTransfer($tg));

            $message = $tg->trans('reply.created', domain: 'create');
            $message = $tg->okText($message);

            $this->state->setCreatedId($feedback->getId());

            $tg->reply($message);

            if ($this->sendToChannelConfirmStep && !empty($this->state->getDescription())) {
                return $this->querySendToChannelConfirm($tg);
            }

            $tg->stopConversation($entity);

            if (!empty($this->state->getDescription())) {
                $this->eventBus->dispatch(new FeedbackSendToTelegramChannelConfirmReceivedEvent(feedback: $feedback, addTime: true));
            }

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            if ($exception->isFirstProperty('rating')) {
                $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

                return $this->queryRating($tg, $entity);
            } elseif ($exception->isFirstProperty('description')) {
                $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

                return $this->queryDescription($tg);
            }

            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->querySearchTerm($tg);
        } catch (FeedbackOnOneselfException) {
            $message = $tg->trans('reply.on_self_forbidden', domain: 'create');
            $message = $tg->forbiddenText($message);

            $tg->reply($message);

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
            if ($this->descriptionStep) {
                return $this->queryDescription($tg);
            }

            return $this->queryRating($tg, $entity);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        return $this->createAndReply($tg, $entity);
    }

    public function gotSendToChannelConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySendToChannelConfirm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->querySendToChannelConfirm($tg);
        }

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.will_sent_to_channel', domain: 'create');
        $message = $tg->okText($message);

        $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);

        $this->eventBus->dispatch(new FeedbackSendToTelegramChannelConfirmReceivedEvent(feedbackId: $this->state->getCreatedId(), addTime: true, notifyUser: true));

        return null;
    }
}