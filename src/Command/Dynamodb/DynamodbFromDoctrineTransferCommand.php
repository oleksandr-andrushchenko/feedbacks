<?php

declare(strict_types=1);

namespace App\Command\Dynamodb;

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
        private readonly IdGenerator $idGenerator,
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

        $stats = [
            'level_1_regions' => $this->transferLevel1Regions(),
            'telegram_bots' => $this->transferTelegramBots(),
            'telegram_channels' => $this->transferTelegramChannels(),
            'users' => $this->transferUsers(),
            'messenger_users' => $this->transferMessengerUsers(),
            'feedbacks' => $this->transferFeedbacks(),
            'feedback_searches' => $this->transferFeedbackSearches(),
            'feedback_lookups' => $this->transferFeedbackLookups(),
            'telegram_bot_payments' => $this->transferTelegramBotPayments(),
            'feedback_user_subscriptions' => $this->transferFeedbackUserSubscriptions(),
            'feedback_bot_conversations' => $this->transferTelegramBotConversations(),
            'telegram_bot_payment_methods' => $this->transferTelegramBotPaymentMethods(),
            'telegram_bot_requests' => $this->transferTelegramBotRequests(),
            'telegram_bot_updates' => $this->transferTelegramBotUpdates(),
            'user_contact_messages' => $this->transferUserContactMessages(),
            'feedback_notifications' => $this->transferFeedbackNotifications(),
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
        $affectedRows = 0;
        foreach ($this->level1RegionDoctrineRepository->findAll() as $level1Region) {
            $this->dynamodbEntityManager->persist($level1Region);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferUsers(): int
    {
        $affectedRows = 0;
        foreach ($this->userDoctrineRepository->findAll() as $user) {
            $this->dynamodbEntityManager->persist($user);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferTelegramBots(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotDoctrineRepository->findAll() as $telegramBot) {
            $this->dynamodbEntityManager->persist($telegramBot);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferTelegramChannels(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramChannelDoctrineRepository->findAll() as $telegramChannel) {
            $this->dynamodbEntityManager->persist($telegramChannel);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferMessengerUsers(): int
    {
        $affectedRows = 0;
        foreach ($this->messengerUserDoctrineRepository->findAll() as $messengerUser) {
            $this->dynamodbEntityManager->persist($messengerUser);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferFeedbacks(): int
    {
        $affectedRows = 0;
        foreach ($this->feedbackDoctrineRepository->findAll() as $feedback) {
            foreach ($feedback->getSearchTerms() as $searchTerm) {
                $searchTerm->setId($this->idGenerator->generateId());
                $this->dynamodbEntityManager->persist($searchTerm);
                $feedback->addSearchTermId($searchTerm->getId());
            }
            $this->dynamodbEntityManager->persist($feedback);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferFeedbackSearches(): int
    {
        $affectedRows = 0;
        foreach ($this->feedbackSearchDoctrineRepository->findAll() as $feedbackSearch) {
            $searchTerm = $feedbackSearch->getSearchTerm();
            $searchTerm->setId($this->idGenerator->generateId());
            $this->dynamodbEntityManager->persist($searchTerm);
            $feedbackSearch->setSearchTermId($searchTerm->getId());
            $this->dynamodbEntityManager->persist($feedbackSearch);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferFeedbackLookups(): int
    {
        $affectedRows = 0;
        foreach ($this->feedbackLookupDoctrineRepository->findAll() as $feedbackLookup) {
            $searchTerm = $feedbackLookup->getSearchTerm();
            $searchTerm->setId($this->idGenerator->generateId());
            $this->dynamodbEntityManager->persist($searchTerm);
            $feedbackLookup->setSearchTermId($searchTerm->getId());
            $this->dynamodbEntityManager->persist($feedbackLookup);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferTelegramBotPayments(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotPaymentDoctrineRepository->findAll() as $telegramBotPayment) {
            $this->dynamodbEntityManager->persist($telegramBotPayment);
            $affectedRows++;
        }
        return $affectedRows;
    }

    private function transferFeedbackUserSubscriptions(): int
    {
        $affectedRows = 0;
        foreach ($this->feedbackUserSubscriptionDoctrineRepository->findAll() as $feedbackUserSubscription) {
            $this->dynamodbEntityManager->persist($feedbackUserSubscription);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferTelegramBotConversations(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotConversationDoctrineRepository->findAll() as $telegramBotConversation) {
            $this->dynamodbEntityManager->persist($telegramBotConversation);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferTelegramBotPaymentMethods(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotPaymentMethodDoctrineRepository->findAll() as $telegramBotPaymentMethod) {
            $this->dynamodbEntityManager->persist($telegramBotPaymentMethod);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferTelegramBotRequests(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotRequestDoctrineRepository->findAll() as $telegramBotRequest) {
            $this->dynamodbEntityManager->persist($telegramBotRequest);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferTelegramBotUpdates(): int
    {
        $affectedRows = 0;
        foreach ($this->telegramBotUpdateDoctrineRepository->findAll() as $telegramBotUpdate) {
            $this->dynamodbEntityManager->persist($telegramBotUpdate);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferUserContactMessages(): int
    {
        $affectedRows = 0;
        foreach ($this->userContactMessageDoctrineRepository->findAll() as $userContactMessage) {
            $this->dynamodbEntityManager->persist($userContactMessage);
            $affectedRows++;
        }
        return $affectedRows;
    }

    public function transferFeedbackNotifications(): int
    {
        $affectedRows = 0;
        foreach ($this->feedbackNotificationDoctrineRepository->findAll() as $feedbackNotification) {
            $this->dynamodbEntityManager->persist($feedbackNotification);
            $affectedRows++;
        }
        return $affectedRows;
    }
}