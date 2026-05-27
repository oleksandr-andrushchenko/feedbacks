<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\Rating;
use App\Exception\ValidatorException;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackCreator;
use App\Service\Feedback\LLM\FeedbackDetailsExtractor;
use App\Service\Feedback\Rating\FeedbackRatingProvider;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\MultipleSearchTermTelegramViewProvider;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\SearchTermsTransfer;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @property CreateFeedbackTelegramBotConversationState $state
 */
class CreateFeedbackV2TelegramBotConversation extends CreateFeedbackTelegramBotConversation implements TelegramBotConversationInterface
{
    public const int STEP_DETAILS_QUERIED = 10;

    public function __construct(
        private readonly Validator $v2Validator,
        private readonly FeedbackDetailsExtractor $feedbackDetailsExtractor,
        Validator $validator,
        SearchTermParserInterface $searchTermParser,
        ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        MultipleSearchTermTelegramViewProvider $multipleSearchTermTelegramViewProvider,
        SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        SearchTermTypeProvider $searchTermTypeProvider,
        FeedbackCreator $feedbackCreator,
        FeedbackRatingProvider $feedbackRatingProvider,
        MessageBusInterface $eventBus,
    )
    {
        parent::__construct(
            $validator,
            $searchTermParser,
            $chooseActionTelegramChatSender,
            $multipleSearchTermTelegramViewProvider,
            $searchTermTelegramViewProvider,
            $searchTermTypeProvider,
            $feedbackCreator,
            $feedbackRatingProvider,
            $eventBus,
        );
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_DETAILS_QUERIED => $this->gotDetails($tg, $entity),
            self::STEP_MEDIA_QUERIED => $this->gotMedia($tg, $entity),
        };
    }

    public function getStep(int $num, string $symbols = ''): string
    {
        if ($num > 2) {
            $num = 2;
        }

        return sprintf('[%d/%d%s] ', $num, 2, $symbols);
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->queryDetails($tg);
    }

    public function queryDetails(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $this->getDetailsQuery($tg, $help);
        $buttons = [];

        if ($this->hasRequiredDetails()) {
            $buttons[] = $tg->crossMarkButton($this->state->getDescription());
            $buttons[] = $tg->nextButton();
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getDetailsQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.details', domain: 'create');
        $query = $tg->queryText($query);

        if (!$help) {
            $query .= $tg->queryTipText($tg->trans('query.details_tip', domain: 'create'));
        }

        if ($this->state->getDescription() !== null) {
            $query .= $tg->alreadyAddedText($this->state->getDescription());
        }

        $query .= $tg->queryTipText($tg->useText(true));

        return $query;
    }

    private function hasRequiredDetails(): bool
    {
        return $this->state->getDescription() !== null
            && $this->state->getSearchTerms()->hasItems()
            && $this->state->getRating() !== null;
    }

    public function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
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
                $this->v2Validator->validate($searchTerm);
            }

            $this->v2Validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $this->restoreExtractedDetails($originalSearchTerms, $originalRating, $originalDescription);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (Throwable) {
            $this->restoreExtractedDetails($originalSearchTerms, $originalRating, $originalDescription);
            $tg->replyWarning($tg->queryText($tg->trans('reply.extraction_failed', domain: 'create')));

            return $this->queryDetails($tg);
        }

        return $this->queryMedia($tg);
    }

    public function queryMedia(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        if (!$this->hasRequiredDetails()) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

            return $this->queryDetails($tg);
        }

        $this->state->setStep(self::STEP_MEDIA_QUERIED);

        $message = $this->getMediaQuery($tg, $help);

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

    private function restoreExtractedDetails(
        SearchTermsTransfer $searchTerms,
        ?Rating $rating,
        ?string $description,
    ): void
    {
        $this->state
            ->setSearchTerms($searchTerms)
            ->setRating($rating)
            ->setDescription($description)
        ;
    }

    public function gotMedia(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryDetails($tg);
        }

        if (!$this->hasRequiredDetails()) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

            return $this->queryDetails($tg);
        }

        return parent::gotMedia($tg, $entity);
    }

    public function querySearchTerm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        return $this->queryDetails($tg, $help);
    }

    public function queryRating(TelegramBotAwareHelper $tg, Entity $entity, bool $help = false): null
    {
        return $this->queryDetails($tg, $help);
    }

    public function queryDescription(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        return $this->queryDetails($tg, $help);
    }

    public function createAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if (!$this->hasRequiredDetails()) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

            return $this->queryDetails($tg);
        }

        try {
            return parent::createAndReply($tg, $entity);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        }
    }
}
