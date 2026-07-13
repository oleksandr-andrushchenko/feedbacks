<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Search\SearchProviderName;
use App\Exception\ValidatorException;
use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\LLM\FeedbackSearchTermsExtractor;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Search\Searcher;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @property SearchFeedbackTelegramBotConversationState $state
 */
class SearchFeedbackTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_DETAILS_QUERIED = 10;
    public const int STEP_CANCEL_PRESSED = 20;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackSearchTermsExtractor $feedbackSearchTermsExtractor,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly FeedbackSearchCreator $feedbackSearchCreator,
        private readonly Searcher $searcher,
        private readonly LoggerInterface $logger,
        private readonly array $searchProviders,
    )
    {
        parent::__construct(new SearchFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->queryDetails($tg),
            self::STEP_DETAILS_QUERIED => $this->gotDetails($tg, $entity),
        };
    }

    private function queryDetails(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $tg->v('search-feedback-details-query');

        $buttons = [
            $tg->cancelButton(),
        ];

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDetails($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $original = $this->state->getSearchTerms();

        try {
            $details = $tg->getText()->getRawValue();
            $searchTerms = $this->feedbackSearchTermsExtractor->extract($details);

            $this->state->setSearchTerms($searchTerms);

            if (!$this->state->getSearchTerms()?->hasItems()) {
                $message = $tg->queryText($tg->t('add_more_details', [], 'search-feedback'));
                $tg->replyWarning($message);

                return $this->queryDetails($tg);
            }

            $context = [
                'country_codes' => array_unique([
                    $tg->getBot()->getEntity()->getCountryCode(),
                    $tg->getCountryCode(),
                ]),
            ];

            foreach ($searchTerms->getItemsAsArray() as $searchTerm) {
                if ($searchTerm->getType() === null) {
                    $this->searchTermParser->parseWithGuessType($searchTerm, context: $context);
                } else {
                    $this->searchTermParser->parseWithKnownType($searchTerm, context: $context);
                }
                $this->validator->validate($searchTerm);
            }

            $this->state->setSearchTerms($searchTerms);

            $feedbackSearchTerms = [];
            foreach ($this->state->getSearchTerms()->getItemsAsArray() as $searchTerm) {
                $feedbackSearch = $this->feedbackSearchCreator->createFeedbackSearch(
                    new FeedbackSearchTransfer(
                        $tg->getBot()->getMessengerUser(),
                        $searchTerm,
                        $tg->getBot()->getEntity()
                    )
                );
                $feedbackSearchTerms[] = $feedbackSearch->getSearchTerm();
            }

            $context = [
                'bot' => $tg->getBot()->getEntity(),
                'countryCode' => $tg->getBot()->getEntity()->getCountryCode(),
                'full' => $tg->getBot()->getUser()?->getSubscriptionExpireAt() > new DateTimeImmutable(),
                'addCountry' => true,
                'addTime' => true,
            ];
            $render = static fn (string $message) => $tg->reply($message);
            $providers = array_map(static fn (string $name): SearchProviderName => SearchProviderName::fromName($name), $this->searchProviders);
            array_unshift($providers, SearchProviderName::feedbacks);

            $this->searcher->search($feedbackSearchTerms, $render, $context, $providers);

            $tg->stopConversation($entity);

            $parameters = [
                'search_terms' => implode(PHP_EOL, array_map(
                    fn (SearchTermTransfer $searchTerm): string => $this->searchTermTelegramViewProvider
                        ->getSearchTermTelegramView($searchTerm, forceType: false, locale: $tg->getLocaleCode()),
                    $this->state->getSearchTerms()->getItems()
                )),
            ];
            $message = $tg->t('will_notify', $parameters, 'search-feedback');

            $message = $tg->okText($tg->queryText($message));
            $message .= PHP_EOL;

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
        } catch (ValidatorException $exception) {
            $this->logger->error($exception);

            $this->state->setSearchTerms($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            $this->state->setSearchTerms($original);
            $message = $tg->queryText($tg->t('error', [], 'search-feedback'));
            $tg->replyWarning($message);

            return $this->queryDetails($tg);
        }
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->t('canceled', [], 'search-feedback');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }
}
