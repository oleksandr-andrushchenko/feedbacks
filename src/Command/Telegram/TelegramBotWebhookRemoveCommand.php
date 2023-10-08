<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Exception\Telegram\TelegramNotFoundException;
use App\Repository\Telegram\TelegramBotRepository;
use App\Service\Telegram\Api\TelegramWebhookRemover;
use App\Service\Telegram\TelegramRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotWebhookRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramRegistry $registry,
        private readonly TelegramWebhookRemover $remover,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->setDescription('Remove telegram bot webhook')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $bot = $this->repository->findOneByUsername($username);

        if ($bot === null) {
            throw new TelegramNotFoundException($username);
        }

        if (!$bot->webhookSet()) {
            $io->warning('No webhook found for remove');

            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(
                    sprintf('Continue removing "%s" telegram bot webhook anyway?', $bot->getUsername()),
                    true
                )
            );
        }

        $confirmed = $confirmed ?? $io->askQuestion(
            new ConfirmationQuestion(
                sprintf('Are you sure you want to remove "%s" telegram bot webhook?', $bot->getUsername()),
                true
            )
        );

        if (!$confirmed) {
            $io->warning(
                sprintf('"%s" telegram bot webhook removing has been cancelled', $bot->getUsername())
            );

            return Command::SUCCESS;
        }

        $telegram = $this->registry->getTelegram($bot);

        $this->remover->removeTelegramWebhook($telegram);
        $bot->setWebhookSet(false);

        $this->entityManager->flush();

        $io->success('Telegram bot webhook has been removed');

        return Command::SUCCESS;
    }
}