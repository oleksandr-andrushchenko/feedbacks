<?php

declare(strict_types=1);

namespace App\Command\Dynamodb;

use App\Entity\Feedback\SearchTerm;
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
        private readonly EntityManagerInterface $doctrineEntityManager,
        private readonly EntityManager $dynamodbEntityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Load data from doctrine to your dynamodb database')
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
            'messenger_users' => $this->transferMessengerUsers(),
            'feedbacks' => $this->transferFeedbacks($telegramBotIdMap),
            'feedback_searches' => $this->transferFeedbackSearches($telegramBotIdMap),
            'feedback_lookups' => $this->transferFeedbackLookups($telegramBotIdMap),
            'telegram_bot_payment_methods' => $this->transferTelegramBotPaymentMethods($telegramBotIdMap, $telegramBotPaymentMethodIdMap),
            'telegram_bot_payments' => $this->transferTelegramBotPayments($telegramBotIdMap, $telegramBotPaymentMethodIdMap),
            'feedback_user_subscriptions' => $this->transferFeedbackUserSubscriptions(),
            'feedback_bot_conversations' => $this->transferTelegramBotConversations($telegramBotIdMap),
//            'telegram_bot_requests' => $this->transferTelegramBotRequests(),
//            'telegram_bot_updates' => $this->transferTelegramBotUpdates(),
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
            $telegramBot->setId($telegramBotIdMap[$telegramBot->getId()]);
            $this->dynamodbEntityManager->persist($telegramBot);
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
            $telegramChannel->setId($this->idGenerator->generateId());
            $this->dynamodbEntityManager->persist($telegramChannel);
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

    private function transferMessengerUsers(): int
    {
        $this->doctrineEntityManager->clear();
        $affectedRows = 0;
        foreach ($this->messengerUserDoctrineRepository->findAll() as $messengerUser) {
            $messengerUser->setUserId($messengerUser->getUser()?->getId());
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
                $searchTerm->setId($this->idGenerator->generateId())
                    ->setMessengerUserId($searchTerm->getMessengerUser()?->getId())
                ;
                $this->dynamodbEntityManager->persist($searchTerm);

                $extraSearchTerms = array_values(array_filter($searchTerms, static fn ($otherSearchTerm) => $otherSearchTerm->getId() !== $searchTerm->getId()));
                $searchTermFeedback = $this->searchTermFeedbackFactory->createSearchTermFeedback($searchTerm, $feedback, empty($extraSearchTerms) ? null : $extraSearchTerms);
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

            $searchTerm->setId($this->idGenerator->generateId())
                ->setMessengerUserId($searchTerm->getMessengerUser()?->getId())
            ;

            $this->dynamodbEntityManager->persist($searchTerm);

            $feedbackSearch
                ->setSearchTermId($searchTerm->getId())
                ->setUserId($feedbackSearch->getUser()?->getId())
                ->setMessengerUserId($feedbackSearch->getMessengerUser()?->getId())
                ->setTelegramBotId($telegramBotId)
            ;
            $this->dynamodbEntityManager->persist($feedbackSearch);

            $searchTermFeedbackSearch = $this->searchTermFeedbackSearchFactory->createSearchTermFeedbackSearch($searchTerm, $feedbackSearch);
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

            $searchTerm->setId($this->idGenerator->generateId())
                ->setMessengerUserId($searchTerm->getMessengerUser()?->getId())
            ;

            $this->dynamodbEntityManager->persist($searchTerm);

            $feedbackLookup
                ->setSearchTermId($searchTerm->getId())
                ->setUserId($feedbackLookup->getUser()?->getId())
                ->setMessengerUserId($feedbackLookup->getMessengerUser()?->getId())
                ->setTelegramBotId($telegramBotId)
            ;
            $this->dynamodbEntityManager->persist($feedbackLookup);

            $searchTermFeedbackLookup = $this->searchTermFeedbackLookupFactory->createSearchTermFeedbackLookup($searchTerm, $feedbackLookup);
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
            $telegramBotConversation
                ->setTelegramBotId($telegramBotIdMap[$telegramBotConversation->getTelegramBotId()] ?? null)
            ;
            $this->dynamodbEntityManager->persist($telegramBotConversation);
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
            $telegramBotPaymentMethod
                ->setId($telegramBotPaymentMethodIdMap[$telegramBotPaymentMethod->getId()])
                ->setTelegramBotId($telegramBotIdMap[$telegramBotPaymentMethod->getTelegramBotId()] ?? null)
            ;
            $this->dynamodbEntityManager->persist($telegramBotPaymentMethod);
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