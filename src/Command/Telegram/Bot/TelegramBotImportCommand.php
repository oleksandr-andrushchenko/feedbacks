<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Model\ImportResult;
use App\Service\Doctrine\DryRunner;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\TelegramBotImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotImportCommand extends Command
{
    public function __construct(
        private readonly string $dataDir,
        private readonly TelegramBotImporter $telegramBotImporter,
        private readonly DryRunner $dryRunner,
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
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Base Filename name to import')
            ->addOption('drop-existing', mode: InputOption::VALUE_NONE, description: 'Drop existing bots')
            ->addOption('sync-descriptions', mode: InputOption::VALUE_NONE, description: 'Sync bots descriptions (with telegram)')
            ->addOption('sync-webhooks', mode: InputOption::VALUE_NONE, description: 'Sync bots webhooks (with telegram)')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Import telegram bots')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filename = $this->dataDir . '/' . $input->getArgument('file');
        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(sprintf('Are you sure you want to import "%s" Telegram bots file?', $filename), true)
            );

            if (!$confirmed) {
                $io->warning(
                    sprintf('"%s" Telegram bots file import has been cancelled', $filename)
                );

                return Command::SUCCESS;
            }
        }

        $mode = 0;
        $mode |= $input->getOption('drop-existing') ? TelegramBotImporter::MODE_DROP_EXISTING : 0;
        $mode |= $input->getOption('sync-descriptions') ? TelegramBotImporter::MODE_SYNC_DESCRIPTIONS : 0;
        $mode |= $input->getOption('sync-webhooks') ? TelegramBotImporter::MODE_SYNC_WEBHOOKS : 0;
        $logger = static fn (string $message) => $io->note($message);
        $func = fn () => $this->telegramBotImporter->importTelegramBots($filename, $mode, $logger);

        if ($dryRun) {
            $result = $this->dryRunner->dryRun($func, readUncommitted: true);
        } else {
            $result = $this->entityManager->wrapInTransaction($func);
        }

        /** @var ImportResult $result */

        $io->success(
            sprintf(
                'Telegram bots have been imported, created: %d, updated: %d, deleted: %d, restored: %d, unchanged: %d, skipped: %d, failed: %d',
                $result->getCreatedCount(),
                $result->getUpdatedCount(),
                $result->getDeletedCount(),
                $result->getRestoredCount(),
                $result->getUnchangedCount(),
                $result->getSkippedCount(),
                $result->getFailedCount(),
            )
        );

        return Command::SUCCESS;
    }
}