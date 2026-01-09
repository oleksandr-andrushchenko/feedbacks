<?php

declare(strict_types=1);

namespace App\Command\Dynamodb;

use App\Entity\Feedback\SearchTerm;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Entity\Telegram\TelegramChannel;
use App\Factory\Feedback\SearchTermFeedbackFactory;
use App\Factory\Feedback\SearchTermFeedbackLookupFactory;
use App\Factory\Feedback\SearchTermFeedbackSearchFactory;
use App\Repository\Feedback\FeedbackDoctrineRepository;
use App\Repository\Feedback\FeedbackLookupDoctrineRepository;
use App\Repository\Feedback\FeedbackNotificationDoctrineRepository;
use App\Repository\Feedback\FeedbackSearchDoctrineRepository;
use App\Repository\Feedback\FeedbackUserSubscriptionDoctrineRepository;
use App\Repository\Intl\Level1RegionDoctrineRepository;
use App\Repository\Messenger\MessengerUserDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotConversationDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotRequestDoctrineRepository;
use App\Repository\Telegram\Bot\TelegramBotUpdateDoctrineRepository;
use App\Repository\Telegram\Channel\TelegramChannelDoctrineRepository;
use App\Repository\User\UserContactMessageDoctrineRepository;
use App\Repository\User\UserDoctrineRepository;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;
use Doctrine\ORM\EntityManagerInterface;
use OA\Dynamodb\ODM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @see DynamodbFromDoctrineTransferCommand
 */
class DynamodbFromDoctrineTransferCommand extends Command
{
    public function __construct(
        private readonly TelegramBotDoctrineRepository $telegramBotDoctrineRepository,
        private readonly TelegramChannelDoctrineRepository $telegramChannelDoctrineRepository,
        private readonly Level1RegionDoctrineRepository $level1RegionDoctrineRepository,
        private readonly UserDoctrineRepository $userDoctrineRepository,
        private readonly MessengerUserDoctrineRepository $messengerUserDoctrineRepository,
        private readonly FeedbackDoctrineRepository $feedbackDoctrineRepository,
        private readonly FeedbackSearchDoctrineRepository $feedbackSearchDoctrineRepository,
        private readonly FeedbackLookupDoctrineRepository $feedbackLookupDoctrineRepository,
        private readonly TelegramBotPaymentDoctrineRepository $telegramBotPaymentDoctrineRepository,
        private readonly FeedbackUserSubscriptionDoctrineRepository $feedbackUserSubscriptionDoctrineRepository,
        private readonly TelegramBotConversationDoctrineRepository $telegramBotConversationDoctrineRepository,
        private readonly TelegramBotPaymentMethodDoctrineRepository $telegramBotPaymentMethodDoctrineRepository,
        private readonly TelegramBotRequestDoctrineRepository $telegramBotRequestDoctrineRepository,
        private readonly TelegramBotUpdateDoctrineRepository $telegramBotUpdateDoctrineRepository,
        private readonly UserContactMessageDoctrineRepository $userContactMessageDoctrineRepository,
        private readonly FeedbackNotificationDoctrineRepository $feedbackNotificationDoctrineRepository,
        private readonly SearchTermFeedbackFactory $searchTermFeedbackFactory,
        private readonly SearchTermFeedbackSearchFactory $searchTermFeedbackSearchFactory,
        private readonly SearchTermFeedbackLookupFactory $searchTermFeedbackLookupFactory,
        private readonly IdGenerator $idGenerator,
        private readonly TelegramBotConversationManager $telegramBotConversationManager,
        private readonly EntityManagerInterface $doctrineEntityManager,
        private readonly EntityManager $dynamodbEntityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Load data from doctrine to your dynamodb database')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $telegramBotIdMap = [];
        $telegramBotPaymentMethodIdMap = [];

        $stats = [
            'level_1_regions' => $this->transferLevel1Regions(),
            'telegram_bots' => $this->transferTelegramBots($telegramBotIdMap),
            'telegram_channels' => $this->transferTelegramChannels(),
            'users' => $this->transferUsers(),
            'messenger_users' => $this->transferMessengerUsers($telegramBotIdMap),
            'feedbacks' => $this->transferFeedbacks($telegramBotIdMap),
            'feedback_searches' => $this->transferFeedbackSearches($telegramBotIdMap),
            'feedback_lookups' => $this->transferFeedbackLookups($telegramBotIdMap),
            'telegram_bot_payment_methods' => $this->transferTelegramBotPaymentMethods($telegramBotIdMap, $telegramBotPaymentMethodIdMap),
            'telegram_bot_payments' => $this->transferTelegramBotPayments($telegramBotIdMap, $telegramBotPaymentMethodIdMap),
            'feedback_user_subscriptions' => $this->transferFeedbackUserSubscriptions(),
            'feedback_bot_conversations' => $this->transferTelegramBotConversations($telegramBotIdMap),
            'telegram_bot_requests' => $this->transferTelegramBotRequests(),
            'telegram_bot_updates' => $this->transferTelegramBotUpdates(),
            'user_contact_messages' => $this->transferUserContactMessages($telegramBotIdMap),
            'feedback_notifications' => $this->transferFeedbackNotifications($telegramBotIdMap),
        ];

        $this->dynamodbEntityManager->flush();

        $io->section('Transfer Summary');
        $io->table(
            ['Entity', 'Affected Rows'],
            array_map(
                fn ($key, $value) => [$key, $value],
                array_keys($stats),
                $stats
            )
        );
        $io->success('Transfer completed.');

        return Command::SUCCESS;
    }

    private function transferLevel1Regions(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->level1RegionDoctrineRepository->findAll() as $level1Region) {
            $this->dynamodbEntityManager->persist($level1Region);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferTelegramBots(array &$telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotDoctrineRepository->findAll() as $telegramBot) {
            $telegramBotIdMap[$telegramBot->getId()] = $this->idGenerator->generateId();
            $newTelegramBot = new TelegramBot(
                $telegramBotIdMap[$telegramBot->getId()],
                $telegramBot->getUsername(),
                $telegramBot->getGroup(),
                $telegramBot->getName(),
                $telegramBot->getToken(),
                $telegramBot->getCountryCode(),
                $telegramBot->getLocaleCode(),
                $telegramBot->checkUpdates(),
                $telegramBot->checkRequests(),
                $telegramBot->acceptPayments(),
                $telegramBot->getAdminIds(),
                $telegramBot->adminOnly(),
                $telegramBot->primary(),
                $telegramBot->descriptionsSynced(),
                $telegramBot->webhookSynced(),
                $telegramBot->commandsSynced(),
                $telegramBot->getCreatedAt()
            );
            $this->dynamodbEntityManager->persist($newTelegramBot);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferTelegramChannels(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramChannelDoctrineRepository->findAll() as $telegramChannel) {
            $newTelegramChannel = new TelegramChannel(
                $this->idGenerator->generateId(),
                $telegramChannel->getUsername(),
                $telegramChannel->getGroup(),
                $telegramChannel->getName(),
                $telegramChannel->getCountryCode(),
                $telegramChannel->getLocaleCode(),
                $telegramChannel->getLevel1RegionId(),
                $telegramChannel->getChatId(),
                $telegramChannel->primary(),
                $telegramChannel->getCreatedAt()
            );
            $this->dynamodbEntityManager->persist($newTelegramChannel);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferUsers(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->userDoctrineRepository->findAll() as $user) {
            $this->dynamodbEntityManager->persist($user);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferMessengerUsers(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->messengerUserDoctrineRepository->findAll() as $messengerUser) {
            $messengerUser->setUserId($messengerUser->getUser()?->getId());
            foreach ($messengerUser->getTelegramBotIds() as $telegramBotId) {
                $newTelegramBotId = $telegramBotIdMap[$telegramBotId] ?? null;
                if ($newTelegramBotId === null) {
                    continue;
                }
                $messengerUser->removeTelegramBotId($telegramBotId)->addTelegramBotId($newTelegramBotId);
            }
            $this->dynamodbEntityManager->persist($messengerUser);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferFeedbacks(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->feedbackDoctrineRepository->findAll() as $feedback) {
            $telegramBotId = $telegramBotIdMap[$feedback->getTelegramBot()?->getId()] ?? null;
            /** @var SearchTerm[] $searchTerms */
            $searchTerms = $feedback->getSearchTerms()->toArray();
            $feedback->setTelegramBotId($telegramBotId)
                ->setSearchTermIds(array_map(static fn ($term) => $term->getId(), $searchTerms))
                ->setUserId($feedback->getUser()?->getId())
                ->setMessengerUserId($feedback->getMessengerUser()?->getId())
                ->setHasActiveSubscription($feedback->hasActiveSubscription() ? true : null)
            ;
            foreach ($searchTerms as $searchTerm) {
                $newSearchTerm = new SearchTerm(
                    $this->idGenerator->generateId(),
                    $searchTerm->getText(),
                    $searchTerm->getNormalizedText(),
                    $searchTerm->getType(),
                    $searchTerm->getMessengerUser(),
                    $searchTerm->getCreatedAt()
                );
                $this->dynamodbEntityManager->persist($newSearchTerm);

                $extraSearchTerms = array_values(array_filter($searchTerms, static fn ($otherSearchTerm) => $otherSearchTerm->getId() !== $newSearchTerm->getId()));
                $searchTermFeedback = $this->searchTermFeedbackFactory->createSearchTermFeedback($newSearchTerm, $feedback, empty($extraSearchTerms) ? null : $extraSearchTerms);
                $searchTermFeedback->setTelegramBotId($telegramBotId);
                $this->dynamodbEntityManager->persist($searchTermFeedback);
            }
            $this->dynamodbEntityManager->persist($feedback);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferFeedbackSearches(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->feedbackSearchDoctrineRepository->findAllWithSearchTerms() as $feedbackSearch) {
            $telegramBotId = $telegramBotIdMap[$feedbackSearch->getTelegramBot()?->getId()] ?? null;
            $searchTerm = $feedbackSearch->getSearchTerm();

            $newSearchTerm = new SearchTerm(
                $this->idGenerator->generateId(),
                $searchTerm->getText(),
                $searchTerm->getNormalizedText(),
                $searchTerm->getType(),
                $searchTerm->getMessengerUser(),
                $searchTerm->getCreatedAt()
            );
            $this->dynamodbEntityManager->persist($newSearchTerm);

            $feedbackSearch
                ->setSearchTermId($newSearchTerm->getId())
                ->setUserId($feedbackSearch->getUser()?->getId())
                ->setMessengerUserId($feedbackSearch->getMessengerUser()?->getId())
                ->setTelegramBotId($telegramBotId)
            ;
            $this->dynamodbEntityManager->persist($feedbackSearch);

            $searchTermFeedbackSearch = $this->searchTermFeedbackSearchFactory->createSearchTermFeedbackSearch($newSearchTerm, $feedbackSearch);
            $this->dynamodbEntityManager->persist($searchTermFeedbackSearch);

            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferFeedbackLookups(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->feedbackLookupDoctrineRepository->findAllWithSearchTerms() as $feedbackLookup) {
            $telegramBotId = $telegramBotIdMap[$feedbackLookup->getTelegramBot()?->getId()] ?? null;
            $searchTerm = $feedbackLookup->getSearchTerm();

            $newSearchTerm = new SearchTerm(
                $this->idGenerator->generateId(),
                $searchTerm->getText(),
                $searchTerm->getNormalizedText(),
                $searchTerm->getType(),
                $searchTerm->getMessengerUser(),
                $searchTerm->getCreatedAt()
            );
            $this->dynamodbEntityManager->persist($newSearchTerm);

            $feedbackLookup
                ->setSearchTermId($newSearchTerm->getId())
                ->setUserId($feedbackLookup->getUser()?->getId())
                ->setMessengerUserId($feedbackLookup->getMessengerUser()?->getId())
                ->setTelegramBotId($telegramBotId)
            ;
            $this->dynamodbEntityManager->persist($feedbackLookup);

            $searchTermFeedbackLookup = $this->searchTermFeedbackLookupFactory->createSearchTermFeedbackLookup($newSearchTerm, $feedbackLookup);
            $this->dynamodbEntityManager->persist($searchTermFeedbackLookup);

            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferTelegramBotPayments(array $telegramBotIdMap, array $telegramBotPaymentMethodIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotPaymentDoctrineRepository->findAll() as $telegramBotPayment) {
            $telegramBotPayment
                ->setTelegramBotPaymentMethodId($telegramBotPaymentMethodIdMap[$telegramBotPayment->getTelegramBotPaymentMethod()?->getId()] ?? null)
                ->setMessengerUserId($telegramBotPayment->getMessengerUser()?->getId())
                ->setTelegramBotId($telegramBotIdMap[$telegramBotPayment->getTelegramBot()?->getId()] ?? null)
            ;
            $this->dynamodbEntityManager->persist($telegramBotPayment);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    private function transferFeedbackUserSubscriptions(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->feedbackUserSubscriptionDoctrineRepository->findAll() as $feedbackUserSubscription) {
            $feedbackUserSubscription
                ->setTelegramPaymentId($feedbackUserSubscription->getTelegramPayment()?->getId())
                ->setMessengerUserId($feedbackUserSubscription->getMessengerUser()?->getId())
                ->setUserId($feedbackUserSubscription->getUser()?->getId())
            ;
            $this->dynamodbEntityManager->persist($feedbackUserSubscription);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferTelegramBotConversations(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotConversationDoctrineRepository->findAll() as $telegramBotConversation) {
            $telegramBotId = $telegramBotIdMap[$telegramBotConversation->getTelegramBotId()] ?? null;
            if ($telegramBotId === null) {
                continue;
            }
            $newTelegramBotConversation = new TelegramBotConversation(
                $this->telegramBotConversationManager->createTelegramConversationHash(
                    $telegramBotConversation->getMessengerUserId(),
                    $telegramBotConversation->getChatId(),
                    $telegramBotId,
                ),
                $telegramBotConversation->getMessengerUserId(),
                $telegramBotConversation->getChatId(),
                $telegramBotId,
                $telegramBotConversation->getClass(),
                $telegramBotConversation->getState()
            );
            $this->dynamodbEntityManager->persist($newTelegramBotConversation);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferTelegramBotPaymentMethods(array $telegramBotIdMap, array &$telegramBotPaymentMethodIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotPaymentMethodDoctrineRepository->findAll() as $telegramBotPaymentMethod) {
            $telegramBotPaymentMethodIdMap[$telegramBotPaymentMethod->getId()] = $this->idGenerator->generateId();
            $newTelegramBotPaymentMethod = new TelegramBotPaymentMethod(
                $telegramBotPaymentMethodIdMap[$telegramBotPaymentMethod->getId()],
                $telegramBotPaymentMethod->getTelegramBot(),
                $telegramBotPaymentMethod->getName(),
                $telegramBotPaymentMethod->getToken(),
                $telegramBotPaymentMethod->getCurrencyCodes(),
                $telegramBotPaymentMethod->getCreatedAt(),
            );
            $newTelegramBotPaymentMethod->setTelegramBotId($telegramBotIdMap[$telegramBotPaymentMethod->getTelegramBotId()] ?? null);
            $this->dynamodbEntityManager->persist($newTelegramBotPaymentMethod);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferTelegramBotRequests(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotRequestDoctrineRepository->findAll() as $telegramBotRequest) {
            $this->dynamodbEntityManager->persist($telegramBotRequest);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferTelegramBotUpdates(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->telegramBotUpdateDoctrineRepository->findAll() as $telegramBotUpdate) {
            $this->dynamodbEntityManager->persist($telegramBotUpdate);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferUserContactMessages(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->userContactMessageDoctrineRepository->findAll() as $userContactMessage) {
            $userContactMessage
                ->setTelegramBotId($telegramBotIdMap[$userContactMessage->getTelegramBot()?->getId()] ?? null)
                ->setMessengerUserId($telegramBotIdMap[$userContactMessage->getMessengerUser()?->getId()] ?? null)
            ;
            $this->dynamodbEntityManager->persist($userContactMessage);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }

    public function transferFeedbackNotifications(array $telegramBotIdMap): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->feedbackNotificationDoctrineRepository->findAll() as $feedbackNotification) {
            $feedbackNotification
                ->setMessengerUserId($feedbackNotification->getMessengerUser()?->getId())
                ->setSearchTermId($feedbackNotification->getSearchTerm()?->getId())
                ->setFeedbackId($feedbackNotification->getFeedback()?->getId())
                ->setTargetFeedbackId($feedbackNotification->getTargetFeedback()?->getId())
                ->setFeedbackSearchId($feedbackNotification->getFeedbackSearch()?->getId())
                ->setTargetFeedbackSearchId($feedbackNotification->getTargetFeedbackSearch()?->getId())
                ->setFeedbackLookupId($feedbackNotification->getFeedbackLookup()?->getId())
                ->setTargetFeedbackLookupId($feedbackNotification->getTargetFeedbackLookup()?->getId())
                ->setFeedbackUserSubscriptionId($feedbackNotification->getFeedbackUserSubscription()?->getId())
                ->setTelegramBotId($telegramBotIdMap[$feedbackNotification->getTelegramBot()?->getId()] ?? null)
            ;
            $this->dynamodbEntityManager->persist($feedbackNotification);
            $affectedRows++;
        }
        $this->dynamodbEntityManager->flush();
        return $affectedRows;
    }
}