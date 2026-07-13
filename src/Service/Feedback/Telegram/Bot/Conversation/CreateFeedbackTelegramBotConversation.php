<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\LLM\FeedbackDetailsExtractor;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_MEDIA_QUERIED = 10;
    public const int STEP_CANCEL_PRESSED = 30;
    public const int STEP_DETAILS_QUERIED = 55;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackDetailsExtractor $feedbackDetailsExtractor,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly FeedbackCreator $feedbackCreator,
        private readonly MessageBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->queryMedia($tg),
            self::STEP_MEDIA_QUERIED => $this->gotMedia($tg, $entity),
            self::STEP_DETAILS_QUERIED => $this->gotDetails($tg, $entity),
        };
    }

    private function getStepsCount(): int
    {
        return 2;
    }

    private function queryMedia(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_MEDIA_QUERIED);

        $message = $tg->v('create-feedback-media-query', [
            'step_number' => 1,
            'total_steps' => $this->getStepsCount(),
            'media_count' => $this->state->hasMedia() ? count($this->state->getMedia()) : 0,
        ]);

        $buttons = [];

        if ($this->state->hasMedia()) {
            $buttons[] = $this->getRemoveMediaButton($tg);
        }

        $buttons[] = $tg->nextButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotMedia(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->nextButton()->getText())) {
            return $this->queryDetails($tg);
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

        $original = $this->state->getMedia();
        $this->state->addMedia($tg->getMedia());

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->logger->error($exception);

            $this->state->setMedia($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryMedia($tg);
        }

        return $this->queryDetails($tg);
    }

    private function queryDetails(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $tg->v('create-feedback-details-query', [
            'step_number' => 2,
            'total_steps' => $this->getStepsCount(),
        ]);

        $buttons = [
            $tg->prevButton(),
            $tg->cancelButton(),
        ];

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->getMedia() !== null) {
            $this->state->addMedia($tg->getMedia());

            return null;
        }

        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDetails($tg);
        }

        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryMedia($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $originalDetails = $this->state->getDetails();
        $originalSearchTerms = $this->state->getSearchTerms();
        $originalRating = $this->state->getRating();

        try {
            $details = $tg->getText()->getRawValue();
            $data = $this->feedbackDetailsExtractor->extract($details);

            $this->state
                ->setSearchTerms($data['search_terms'])
                ->setRating($data['rating'])
                ->setDetails($details)
            ;

            $context = [
                'country_codes' => array_unique([
                    $tg->getBot()->getEntity()->getCountryCode(),
                    $tg->getCountryCode(),
                ]),
            ];

            foreach (($this->state->getSearchTerms()?->getItemsAsArray() ?? []) as $searchTerm) {
                if ($searchTerm->getType() === null) {
                    $this->searchTermParser->parseWithGuessType($searchTerm, $context);
                } else {
                    $this->searchTermParser->parseWithKnownType($searchTerm, $context);
                }
                $this->validator->validate($searchTerm);
            }

            $this->validator->validate($this->state);

            // todo: use command bus
            $feedback = $this->feedbackCreator->createFeedback(
                new FeedbackTransfer(
                    messengerUser: $tg->getBot()->getMessengerUser(),
                    searchTerms: $this->state->getSearchTerms(),
                    rating: $this->state->getRating(),
                    description: $this->state->getDetails(),
                    media: $this->state->getMedia(),
                    telegramBot: $tg->getBot()->getEntity()
                )
            );

            $message = $tg->t('created', [], 'create-feedback');
            $message = $tg->okText($message);

            $this->state->setCreatedId($feedback->getId());

            $tg->reply($message);

            $tg->stopConversation($entity);

            $this->eventBus->dispatch(new FeedbackSendToTelegramChannelConfirmReceivedEvent(feedback: $feedback, addTime: true));

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $this->logger->error($exception);

            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (FeedbackOnOneselfException $exception) {
            $this->logger->error($exception);
            $message = $tg->t('on_self_forbidden', [], 'create-feedback');
            $message = $tg->forbiddenText($message);

            $tg->reply($message);

            return $this->queryDetails($tg);
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            $this->state
                ->setSearchTerms($originalSearchTerms)
                ->setRating($originalRating)
                ->setDetails($originalDetails)
            ;
            $message = $exception instanceof ValidatorException
                ? $exception->getFirstMessage()
                : $tg->t('error', [], 'create-feedback');
            $message = $tg->queryText($message);
            $tg->replyWarning($message);

            return $this->queryDetails($tg);
        }
    }

    private function getRemoveMediaButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $parameters = ['count' => count($this->state->getMedia())];
        $message = $tg->t('media_count', $parameters, 'create-feedback');

        return $tg->crossMarkButton($message);
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->t('canceled', [], 'create-feedback');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }
}
