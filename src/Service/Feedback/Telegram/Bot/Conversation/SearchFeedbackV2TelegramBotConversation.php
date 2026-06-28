<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
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
class SearchFeedbackV2TelegramBotConversation extends TelegramBotConversation
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

    private function getStep(int $num): string
    {
        return sprintf('[%d/%d] ', $num, 1);
    }

    private function queryDetails(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_DETAILS_QUERIED);

        $message = $this->getStep(1);
        $message .= $tg->trans('query.search_term', domain: 'search');
        $message = $tg->queryText($message);

        $message .= $tg->queryTipText(rtrim($tg->view('search_term_types', ['types' => SearchTermType::base])));

        if ($this->state->getSearchTerms()?->hasItems()) {
            $message .= $tg->alreadyAddedText(implode(PHP_EOL, array_map(
                fn (SearchTermTransfer $searchTerm): string => '▫️ ' . $this->searchTermTelegramViewProvider
                        ->getSearchTermTelegramReverseView($searchTerm),
                $this->state->getSearchTerms()->getItems()
            )));
        }

        if ($help) {
            $message = $tg->view('search_help', ['query' => $message]);
        } else {
            $message .= $tg->queryTipText($tg->useText(true));
        }

        $buttons = [];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotDetails(TelegramBotAwareHelper $tg, Entity $entity): null
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
            $details = $tg->getText()->getRawValue();
            $searchTerms = $this->feedbackSearchTermsExtractor->extract($details);

            $this->state->setSearchTerms($searchTerms);

            if (!$this->state->getSearchTerms()?->hasItems()) {
                $tg->replyWarning($tg->queryText($tg->trans('reply.details_required', domain: 'create')));

                return $this->queryDetails($tg);
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

    private function searchAndReply(TelegramBotAwareHelper $tg, Entity $entity): null
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

            $message = $tg->trans('reply.will_notify', ['search_terms' => $this->getSearchTermsView($tg)], 'search');

            $message = $tg->okText($tg->queryText($message));
            $message .= PHP_EOL;

//            $message .= PHP_EOL;
//            $message .= $tg->trans('reply.create', [
//                'search_terms' => $this->getSearchTermsView($tg),
//                'create_command' => $tg->command('create', html: true, link: true),
//            ], 'search');
//            $message .= PHP_EOL;

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message, appendDefault: true);
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
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryDetails($tg);
        } catch (Throwable $exception) {
            $this->logger->error($exception);
            $tg->replyWarning($tg->queryText($tg->trans('reply.extraction_failed', domain: 'search')));

            return $this->queryDetails($tg);
        }
    }

    private function getSearchTermsView(TelegramBotAwareHelper $tg): string
    {
        return implode(PHP_EOL, array_map(
            fn (SearchTermTransfer $searchTerm): string => $this->searchTermTelegramViewProvider
                ->getSearchTermTelegramView($searchTerm, forceType: false, locale: $tg->getLocaleCode()),
            $this->state->getSearchTerms()->getItems()
        ));
    }
}
