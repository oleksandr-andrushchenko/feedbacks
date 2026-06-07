<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Longman\TelegramBot\Entities\KeyboardButton;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 *
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_SEARCH_TERM_QUERIED = 10;
    public const int STEP_SEARCH_TERM_TYPE_QUERIED = 20;
    public const int STEP_CANCEL_PRESSED = 30;
    public const int STEP_RATING_QUERIED = 40;
    public const int STEP_DESCRIPTION_QUERIED = 50;
    public const int STEP_MEDIA_QUERIED = 55;
    /** @deprecated */
    public const int STEP_CONFIRM_QUERIED = 60;
    /** @deprecated */
    public const int STEP_SEND_TO_CHANNEL_CONFIRM_QUERIED = 70;

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
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->querySearchTerm($tg),
            self::STEP_SEARCH_TERM_QUERIED => $this->gotSearchTerm($tg, $entity),
            self::STEP_SEARCH_TERM_TYPE_QUERIED => $this->gotSearchTermType($tg, $entity),
            self::STEP_RATING_QUERIED => $this->gotRating($tg, $entity),
            self::STEP_DESCRIPTION_QUERIED => $this->gotDescription($tg, $entity),
            self::STEP_MEDIA_QUERIED => $this->gotMedia($tg, $entity),
        };
    }

    private function getStep(int $num, string $symbols = ''): string
    {
        return sprintf('[%d/%d%s] ', $num, 4, $symbols);
    }

    private function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SEARCH_TERM_QUERIED);

        $searchTerms = $this->state->getSearchTerms();

        if ($searchTerms->hasItems()) {
            $message = $this->getStep(1, '**');
            $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
                $searchTerms,
                forceType: false
            );
            $parameters = [
                'search_term' => $searchTermView,
            ];
            $message .= $tg->trans('query.extra_search_term', parameters: $parameters, domain: 'create');
            $message = $tg->queryText($message, true);
        } else {
            $searchTermView = null;
            $message = $this->getStep(1);
            $message .= $tg->trans('query.search_term', domain: 'create');
            $message = $tg->queryText($message);
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
            $message .= $tg->queryTipText(
                rtrim($tg->view('search_term_types', context: ['types' => $types]))
                . "\n▫️ " . sprintf('<b>[ %s ]</b>', $tg->trans('query.search_term_put_one', domain: 'create'))
            );
        }

        if ($searchTerms->hasItems()) {
            $message .= $tg->alreadyAddedText(implode(PHP_EOL, array_map(
                fn (SearchTermTransfer $searchTerm): string => '▫️ ' . $this->searchTermTelegramViewProvider
                        ->getSearchTermTelegramReverseView($searchTerm),
                $searchTerms->getItems()
            )));
        }

        if ($help) {
            if ($searchTerms->hasItems()) {
                $message = $tg->view('create_extra_search_term_help', [
                    'query' => $message,
                    'search_term' => $searchTermView,
                ]);
            } else {
                $message = $tg->view('create_search_term_help', [
                    'query' => $message,
                ]);
            }
        } else {
            $message .= $tg->queryTipText($tg->useText(true));
        }

        $buttons = [];

        if ($searchTerms->hasItems()) {
            $buttons[] = array_map(
                fn (SearchTermTransfer $searchTerm): KeyboardButton => $this->getRemoveTermButton($searchTerm, $tg),
                $searchTerms->getItems()
            );
            $buttons[] = $tg->nextButton();
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

        $searchTerms = $this->state->getSearchTerms();

        if ($searchTerms->hasItems()) {
            if ($tg->matchInput($tg->nextButton()->getText())) {
                return $this->queryRating($tg, $entity);
            }

            $searchTerm = null;
            foreach ($searchTerms->getItems() as $searchTerm) {
                if ($this->getRemoveTermButton($searchTerm, $tg)->getText() === $tg->getText()->getRawValue()) {
                    break;
                }
            }

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

        $searchTerm = new SearchTermTransfer($tg->getText()->getRawValue());

        $this->parseSearchTerm($searchTerm, $tg);

        try {
            $this->validator->validate($searchTerm);
        } catch (ValidatorException $exception) {
            $tg->replyWarning(implode(PHP_EOL . PHP_EOL, [
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
            } else {
                return $this->querySearchTermType($tg);
            }
        }

        return $this->querySearchTerm($tg);
    }

    private function queryRating(TelegramBotAwareHelper $tg, Entity $entity, bool $help = false): null
    {
        $this->state->setStep(self::STEP_RATING_QUERIED);

        $message = $this->getStep(2);
        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $message .= $tg->trans('query.rating', $parameters, domain: 'create');
        $message = $tg->queryText($message);

        if ($this->state->getRating() !== null) {
            $message .= $tg->alreadyAddedText($this->feedbackRatingProvider->getRatingComposeName($this->state->getRating()));
        }

        if ($help) {
            $message = $tg->view('create_rating_help', [
                'query' => $message,
                'search_term' => $searchTermView,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(false));
        }

        $buttons = [];
        $buttons[] = array_map(fn (Rating $rating): KeyboardButton => $this->getRatingButton($rating, $tg), Rating::cases());

        if ($this->state->getRating() === null) {
            $buttons[] = $tg->prevButton();
        } else {
            $buttons[] = [$tg->prevButton(), $tg->nextButton()];
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getRatingButton(Rating $rating, TelegramBotAwareHelper $tg): KeyboardButton
    {
        $name = $this->feedbackRatingProvider->getRatingComposeName($rating);

        if ($rating === $this->state->getRating()) {
            $name = $tg->selectedText($name);
        }

        return $tg->button($name);
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'create');
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

        $message = $this->getStep(1, '*');
        $searchTerm = $this->state->getSearchTerms()->getLastItem();
        $searchTermView = $searchTerm->getText();
        $parameters = [
            'search_term' => sprintf('<u>%s</u>', $searchTermView),
        ];
        $message .= $tg->trans('query.search_term_type', parameters: $parameters, domain: 'create');
        $message = $tg->queryText($message);

        if ($help) {
            return $tg->view('create_search_term_type_help', [
                'query' => $message,
                'search_term' => $searchTermView,
            ]);
        }

        $message .= $tg->queryTipText($tg->useText(false));

        $buttons = array_map(
            fn (SearchTermType $type): KeyboardButton => $this->getSearchTermTypeButton($type, $tg),
            $this->getSearchTermTypes($searchTerm)
        );
        $buttons[] = $this->getRemoveTermButton($searchTerm, $tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getSearchTermTypeButton(SearchTermType $type, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->searchTermTypeProvider->getSearchTermTypeComposeName($type));
    }

    private function getSearchTermTypes(SearchTermTransfer $searchTerm): array
    {
        $types = $searchTerm->getTypes() ?? [];
        $types = $this->searchTermTypeProvider->sortSearchTermTypes($types);
        $types[] = SearchTermType::unknown;

        return $types;
    }

    private function gotSearchTermType(TelegramBotAwareHelper $tg, Entity $entity): null
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

            return $this->querySearchTermType($tg);
        }

        return $this->querySearchTerm($tg);
    }

    private function gotRating(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(false);

            return $this->queryRating($tg, $entity);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
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

        $rating = null;
        foreach (Rating::cases() as $rating) {
            if ($this->getRatingButton($rating, $tg)->getText() === $tg->getText()->getRawValue()) {
                break;
            }
        }

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

        return $this->queryDescription($tg);
    }

    private function queryDescription(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DESCRIPTION_QUERIED);

        $message = $this->getStep(3);
        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $message .= $tg->trans('query.description', $parameters, domain: 'create');
        $message = $tg->queryText($message, true);

        if (!$help) {
            $message .= $tg->queryTipText($tg->trans('query.description_tip', domain: 'create'));
        }

        if ($this->state->getDescription() !== null) {
            $message .= $tg->alreadyAddedText($this->state->getDescription());
        }

        if ($help) {
            $message = $tg->view('create_description_help', [
                'query' => $message,
                'search_term' => $searchTermView,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(true));
        }

        $buttons = [];

        if ($this->state->getDescription() !== null) {
            $buttons[] = $tg->removeButton($this->state->getDescription());
        }

        $buttons[] = $tg->nextButton();
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotDescription(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDescription($tg);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryRating($tg, $entity);
        }

        if ($tg->matchInput($tg->nextButton()->getText())) {
            return $this->queryMedia($tg);
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
        $this->state->setDescription($tg->getText()->getRawValue());

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->state->setDescription($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDescription($tg);
        }

        return $this->queryMedia($tg);
    }

    private function queryMedia(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $message = $this->getStep(4);
        $searchTermView = $this->multipleSearchTermTelegramViewProvider->getPrimarySearchTermTelegramView(
            $this->state->getSearchTerms(),
            forceType: false
        );
        $parameters = [
            'search_term' => $searchTermView,
        ];
        $message .= $tg->trans('query.media', $parameters, domain: 'create');
        $message = $tg->queryText($message, true);

        if (!$help) {
            $message .= $tg->queryTipText($tg->trans('query.media_tip', domain: 'create'));
        }

        if ($this->state->hasMedia()) {
            $message .= $tg->alreadyAddedText($this->getQueryMediaCountText($tg));
        }

        if ($help) {
            $message = $tg->view('create_media_help', [
                'query' => $message,
                'search_term' => $searchTermView,
            ]);
        } else {
            $message .= $tg->queryTipText($tg->useText(true));
        }

        return $this->queryMediaMessage($tg, $message);
    }

    private function getQueryMediaCountText(TelegramBotAwareHelper $tg): string
    {
        return $tg->trans('query.media_count', ['count' => count($this->state->getMedia())], domain: 'create');
    }

    private function queryMediaMessage(TelegramBotAwareHelper $tg, string $message): null
    {
        $this->state->setStep(self::STEP_MEDIA_QUERIED);

        $buttons = [];

        if ($this->state->hasMedia()) {
            $buttons[] = $this->getRemoveMediaButton($tg);
            $buttons[] = $this->getCreateConfirmButton($tg);
        } else {
            $buttons[] = $this->getSkipAndCreateConfirmButton($tg);
        }

        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getRemoveMediaButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->crossMarkButton($this->getQueryMediaCountText($tg));
    }

    private function getCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->checkMarkButton($tg->trans('keyboard.create_confirm', domain: 'create'));
    }

    private function getSkipAndCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->checkMarkButton($tg->trans('keyboard.skip_and_create_confirm', domain: 'create'));
    }

    private function gotMedia(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryDescription($tg);
        }

        if ($this->state->hasMedia() && $tg->matchInput($this->getCreateConfirmButton($tg)->getText())) {
            return $this->createAndReply($tg, $entity);
        }

        if (!$this->state->hasMedia() && $tg->matchInput($this->getSkipAndCreateConfirmButton($tg)->getText())) {
            return $this->createAndReply($tg, $entity);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryMedia($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($this->state->hasMedia() && $tg->matchInput($this->getRemoveMediaButton($tg)->getText())) {
            $this->state->setMedia(null);

            return $this->queryMedia($tg);
        }

        if ($tg->getMedia() === null) {
            $tg->replyWrong(true);

            return $this->queryMedia($tg);
        }

        if (!$this->state->hasMedia()) {
            $this->queryMediaMessage($tg, $tg->trans('reply.uploads_processing_started'));
        }

        $original = $this->state->getMedia();
        $this->state->addMedia($tg->getMedia());

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->state->setMedia($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryMedia($tg);
        }

        return $this->queryMediaMessage($tg, $tg->trans('reply.upload_uploaded', [
            'count' => count($this->state->getMedia() ?? []),
        ]));
    }

    private function createAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
            $this->validator->validate($this->state);

            // todo: use command bus
            $feedback = $this->feedbackCreator->createFeedback(
                new FeedbackTransfer(
                    messengerUser: $tg->getBot()->getMessengerUser(),
                    searchTerms: $this->state->getSearchTerms(),
                    rating: $this->state->getRating(),
                    description: $this->state->getDescription(),
                    media: $this->state->getMedia(),
                    telegramBot: $tg->getBot()->getEntity()
                )
            );

            $message = $tg->trans('reply.created', domain: 'create');
            $message = $tg->okText($message);

            $this->state->setCreatedId($feedback->getId());

            $tg->reply($message);

            $tg->stopConversation($entity);

            $this->eventBus->dispatch(new FeedbackSendToTelegramChannelConfirmReceivedEvent(feedback: $feedback, addTime: true));

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
            $message = $tg->view('command_limit_exceeded', [
                'command' => 'create',
                'period' => $exception->getLimit()->getPeriod(),
                'count' => $exception->getLimit()->getCount(),
                'limits' => $this->feedbackCreator->getOptions()->getLimits(),
            ]);

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }
    }
}
