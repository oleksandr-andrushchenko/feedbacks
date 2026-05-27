<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\LLM\FeedbackDetailsExtractor;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use Longman\TelegramBot\Entities\KeyboardButton;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackV2TelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_DETAILS_QUERIED = 10;
    public const int STEP_CANCEL_PRESSED = 30;
    public const int STEP_MEDIA_QUERIED = 55;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackDetailsExtractor $feedbackDetailsExtractor,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        private readonly FeedbackCreator $feedbackCreator,
        private readonly MessageBusInterface $eventBus,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->queryDetails($tg),
            self::STEP_DETAILS_QUERIED => $this->gotDetails($tg, $entity),
            self::STEP_MEDIA_QUERIED => $this->gotMedia($tg, $entity),
        };
    }

    private function getStep(int $num, string $symbols = ''): string
    {
        if ($num > 2) {
            $num = 2;
        }

        return sprintf('[%d/%d%s] ', $num, 2, $symbols);
    }

    private function queryDetails(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $this->getStep(1);
        $message .= $tg->trans('query.details', domain: 'create');
        $message = $tg->queryText($message);

        if (!$help) {
            $message .= $tg->queryTipText($tg->trans('query.details_tip', domain: 'create'));
        }

        if ($this->state->getDescription() !== null) {
            $message .= $tg->alreadyAddedText($this->state->getDescription());
        }

        $message .= $tg->queryTipText($tg->useText(true));
        $buttons = [];

        if ($this->hasRequiredDetails()) {
            $buttons[] = $tg->crossMarkButton($this->state->getDescription());
            $buttons[] = $tg->nextButton();
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function hasRequiredDetails(): bool
    {
        return $this->state->getDescription() !== null && $this->state->getSearchTerms()->hasItems() && $this->state->getRating() !== null;
    }

    private function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDetails($tg);
        }

        if ($this->state->getDescription() !== null) {
            if ($tg->matchInput($tg->crossMarkButton($this->state->getDescription())->getText())) {
                $this->state
                    ->setDescription(null)
                    ->setSearchTerms(null)
                    ->setRating(null)
                ;

                return $this->queryDetails($tg);
            }
        }

        if ($this->hasRequiredDetails()) {
            if ($tg->matchInput($tg->nextButton()->getText())) {
                return $this->queryMedia($tg);
            }
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryDetails($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $originalSearchTerms = $this->state->getSearchTerms();
        $originalRating = $this->state->getRating();
        $originalDescription = $this->state->getDescription();
        $description = $tg->getText()->getRawValue();

        try {
            $details = $this->feedbackDetailsExtractor->extract($description);

            $this->state
                ->setDescription($description)
                ->setSearchTerms($details['search_terms'])
                ->setRating($details['rating'])
            ;

            if (!$this->state->getSearchTerms()->hasItems()) {
                throw new RuntimeException('No feedback search terms were extracted.');
            }

            foreach ($this->state->getSearchTerms()->getItemsAsArray() as $searchTerm) {
                $this->parseSearchTerm($searchTerm, $tg);
                $this->validator->validate($searchTerm);
            }

            $this->validator->validate($this->state);
        } catch (Throwable $exception) {
            $this->state
                ->setSearchTerms($originalSearchTerms)
                ->setRating($originalRating)
                ->setDescription($originalDescription)
            ;
            $tg->replyWarning(
                $tg->queryText(
                    $exception instanceof ValidatorException
                        ? $exception->getFirstMessage()
                        : $tg->trans('reply.extraction_failed', domain: 'create')
                )
            );

            return $this->queryDetails($tg);
        }

        return $this->queryMedia($tg);
    }

    private function queryMedia(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        if (!$this->hasRequiredDetails()) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

            return $this->queryDetails($tg);
        }

        $this->state->setStep(self::STEP_MEDIA_QUERIED);

        $message = $this->getStep(2);
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

        $buttons = [];

        if ($this->state->hasMedia()) {
            $buttons[] = $tg->crossMarkButton($this->getQueryMediaCountText($tg));
        }

        $buttons[] = $this->getSkipAndCreateConfirmButton($tg);
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getQueryMediaCountText(TelegramBotAwareHelper $tg): string
    {
        return $tg->trans('query.media_count', ['count' => count($this->state->getMedia())], domain: 'create');
    }

    private function getSkipAndCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->checkMarkButton($tg->trans('keyboard.skip_and_create_confirm', domain: 'create'));
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

    private function gotMedia(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryDetails($tg);
        }

        if (!$this->hasRequiredDetails()) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

            return $this->queryDetails($tg);
        }

        if ($this->state->hasMedia() && $tg->matchInput($this->getCreateConfirmButton($tg)->getText())) {
            return $this->createAndReply($tg, $entity);
        }

        if ($tg->matchInput($this->getSkipAndCreateConfirmButton($tg)->getText())) {
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

    private function getCreateConfirmButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->checkMarkButton($tg->trans('keyboard.create_confirm', domain: 'create'));
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
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (FeedbackOnOneselfException) {
            $message = $tg->trans('reply.on_self_forbidden', domain: 'create');
            $message = $tg->forbiddenText($message);

            $tg->reply($message);

            return $this->queryDetails($tg);
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

    private function getRemoveMediaButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->crossMarkButton($this->getQueryMediaCountText($tg));
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
}
