<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Feedback\SearchTerm;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Search\SearchProviderName;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Model\Feedback\Command\FeedbackCommandLimit;
use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\FeedbackSearchCreator;
use App\Service\Feedback\FeedbackSearcher;
use App\Service\Feedback\LLM\SearchTermsExtractor;
use App\Service\Feedback\SearchTerm\SearchTermParserInterface;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\View\SearchTermTelegramViewProvider;
use App\Service\Search\Searcher;
use App\Service\Search\Viewer\Telegram\FeedbackTelegramSearchViewer;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use App\Transfer\Feedback\SearchTermTransfer;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * @property SearchFeedbackTelegramBotConversationState $state
 */
class SearchFeedbackV2TelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const int STEP_DETAILS_QUERIED = 10;
    public const int STEP_CANCEL_PRESSED = 20;

    public function __construct(
        private readonly Validator $validator,
        private readonly SearchTermsExtractor $searchTermsExtractor,
        private readonly SearchTermParserInterface $searchTermParser,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly FeedbackSearchCreator $feedbackSearchCreator,
        private readonly FeedbackSearcher $feedbackSearcher,
        private readonly FeedbackTelegramSearchViewer $feedbackTelegramSearchViewer,
        private readonly Searcher $searcher,
        private readonly array $searchProviders,
    )
    {
        parent::__construct(new SearchFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_DETAILS_QUERIED => $this->gotDetails($tg, $entity),
        };
    }

    public function getStep(int $num): string
    {
        return sprintf('[%d/%d] ', $num, 1);
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->queryDetails($tg);
    }

    public function queryDetails(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $this->getDetailsQuery($tg, $help);

        return $tg->reply($message, $tg->keyboard(
            $tg->helpButton(),
            $tg->cancelButton()
        ))->null();
    }

    public function getDetailsQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.details', domain: 'search');
        $query = $tg->queryText($query);

        if (!$help) {
            $query .= $tg->queryTipText($tg->trans('query.details_tip', domain: 'search'));
        }

        if ($this->state->getSearchTerms()->hasItems()) {
            $query .= $tg->alreadyAddedText(implode("\n", array_map(
                fn (SearchTermTransfer $searchTerm): string => '▫️ ' . $this->searchTermTelegramViewProvider
                        ->getSearchTermTelegramReverseView($searchTerm),
                $this->state->getSearchTerms()->getItems()
            )));
        }

        $query .= $tg->queryTipText($tg->useText(true));

        return $query;
    }

    public function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryDetails($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryDetails($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        $original = $this->state->getSearchTerms();

        try {
            $searchTerms = $this->searchTermsExtractor->extract($tg->getText()->getRawValue());

            if (!$searchTerms->hasItems()) {
                throw new RuntimeException('No search terms were extracted.');
            }

            foreach ($searchTerms->getItemsAsArray() as $searchTerm) {
                $this->parseSearchTerm($searchTerm, $tg);
                $this->validator->validate($searchTerm);
            }

            $this->state->setSearchTerms($searchTerms);
        } catch (ValidatorException $exception) {
            $this->state->setSearchTerms($original);
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (Throwable) {
            $this->state->setSearchTerms($original);
            $tg->replyWarning($tg->queryText($tg->trans('reply.extraction_failed', domain: 'search')));

            return $this->queryDetails($tg);
        }

        return $this->searchAndReply($tg, $entity);
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

    public function searchAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        try {
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

            $feedbackSearchTerms = array_values(array_filter($feedbackSearchTerms));

            if (count($feedbackSearchTerms) === 0) {
                throw new RuntimeException('No feedback search terms were created.');
            }

            $context = [
                'bot' => $tg->getBot()->getEntity(),
                'countryCode' => $tg->getBot()->getEntity()->getCountryCode(),
                'full' => $tg->getBot()->getUser()?->getSubscriptionExpireAt() > new DateTimeImmutable(),
                'addCountry' => true,
                'addTime' => true,
            ];

            if (count($feedbackSearchTerms) === 1) {
                $this->searchSingleTerm($tg, $feedbackSearchTerms[0], $context);
            } else {
                $this->searchAllTerms($tg, $feedbackSearchTerms, $context);
            }

            $tg->stopConversation($entity);

            $message = $this->getWillNotifyReply($tg);
            $message .= "\n";
            $message .= $this->getCreateReply($tg);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
        } catch (FeedbackCommandLimitExceededException $exception) {
            $message = $this->getLimitExceededReply($tg, $exception->getLimit());

            $tg->reply($message);

            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (Throwable) {
            $tg->replyWarning($tg->queryText($tg->trans('reply.extraction_failed', domain: 'search')));

            return $this->queryDetails($tg);
        }
    }

    private function searchSingleTerm(TelegramBotAwareHelper $tg, SearchTerm $searchTerm, array $context): void
    {
        $render = static fn (string $message) => $tg->reply($message);
        $providers = array_map(static fn (string $name): SearchProviderName => SearchProviderName::fromName($name), $this->searchProviders);
        array_unshift($providers, SearchProviderName::feedbacks);

        $this->searcher->search($searchTerm, $render, $context, $providers);
    }

    /**
     * @param array<SearchTerm> $searchTerms
     */
    private function searchAllTerms(TelegramBotAwareHelper $tg, array $searchTerms, array $context): void
    {
        $tg->reply($tg->trans('reply.searching_by_details', domain: 'search'));

        $feedbacks = $this->feedbackSearcher->searchFeedbacksByAllSearchTerms(
            $searchTerms,
            withUsers: $context['addTime'] ?? false
        );
        $firstSearchTerm = $searchTerms[0];

        if (count($feedbacks) === 0) {
            $tg->reply($this->feedbackTelegramSearchViewer->getEmptyMessage($firstSearchTerm, $context));

            return;
        }

        $tg->reply($this->feedbackTelegramSearchViewer->getResultMessage($feedbacks, $firstSearchTerm, $context));
    }

    public function getWillNotifyReply(TelegramBotAwareHelper $tg): string
    {
        $message = $tg->trans('reply.will_notify_details', [
            'search_terms' => $this->getSearchTermsView($tg),
        ], domain: 'search');

        $message = $tg->okText($tg->queryText($message));
        $message .= "\n";

        return $message;
    }

    private function getSearchTermsView(TelegramBotAwareHelper $tg): string
    {
        return implode("\n", array_map(
            fn (SearchTermTransfer $searchTerm): string => $this->searchTermTelegramViewProvider
                ->getSearchTermTelegramView($searchTerm, forceType: false, localeCode: $tg->getLocaleCode()),
            $this->state->getSearchTerms()->getItems()
        ));
    }

    public function getCreateReply(TelegramBotAwareHelper $tg): string
    {
        $message = $tg->trans('reply.create_details', [
            'search_terms' => $this->getSearchTermsView($tg),
            'create_command' => $tg->command('create', html: true, link: true),
        ], domain: 'search');

        $message = $tg->okText($message);
        $message .= "\n";

        return $message;
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
}
