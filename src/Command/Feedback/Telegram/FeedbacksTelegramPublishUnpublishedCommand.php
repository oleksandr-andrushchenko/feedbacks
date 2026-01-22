<?php

declare(strict_types=1);

namespace App\Command\Feedback\Telegram;

use App\Message\Event\Feedback\FeedbackSendToTelegramChannelConfirmReceivedEvent;
use App\Repository\Feedback\FeedbackRepository;
use App\Service\ORM\EntityManager;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbacksTelegramPublishUnpublishedCommand extends Command
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly MessageBusInterface $eventBus,
        private readonly EntityManager $entityManager,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->addOption('date', mode: InputOption::VALUE_REQUIRED, description: 'Date in mm/dd/yyyy format')
            ->setDescription('Publish unpublished feedbacks in telegram channels')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('date') === null) {
            $feedbacks = $this->feedbackRepository->findAll();
        } else {
            $date = $input->getOption('date');

            $dateTime = DateTimeImmutable::createFromFormat('m/d/Y', $date);
            $from = $dateTime->setTime(0, 0);
            $to = $from->modify('+1 day');

            $feedbacks = $this->feedbackRepository->findUnpublishedByPeriod($from, $to);
        }

        $count = count($feedbacks);

        if ($count === 0) {
            $io->note('Nothing to publish');

            return Command::SUCCESS;
        }

        $confirmed = $io->askQuestion(
            new ConfirmationQuestion(
                sprintf(
                    'There are %d unpublished feedbacks were found%s. Are you sure you want to publish them?',
                    $count,
                    isset($date) ? ' for %s date' : ''
                ),
                true
            )
        );

        if (!$confirmed) {
            $io->warning('Publishing has been cancelled');

            return Command::SUCCESS;
        }

        foreach ($feedbacks as $feedback) {
            $this->eventBus->dispatch(new FeedbackSendToTelegramChannelConfirmReceivedEvent(feedback: $feedback, addTime: true));
            sleep(mt_rand(1, 10));
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d Feedbacks have been published', $count));

        return Command::SUCCESS;
    }
}