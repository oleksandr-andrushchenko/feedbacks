<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Api\TelegramBotCommandsRemover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotCommandsRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotCommandsRemover $telegramBotCommandsRemover,
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
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->setDescription('Remove telegram bot commands')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $bot = $this->telegramBotRepository->findOneNonDeletedByUsername($username);

        if ($bot === null) {
            throw new TelegramBotNotFoundException($username);
        }

        $this->telegramBotCommandsRemover->removeTelegramCommands($bot);

        $this->entityManager->flush();

        $row = [];
        $myCommands = $this->telegramBotCommandsRemover->getMyCommands();

        foreach ($myCommands as $myCommandsItem) {
            $value = sprintf('%s + %s', $myCommandsItem->getLocaleCode(), $myCommandsItem->getScope()->toJson());
            foreach ($myCommandsItem->getCommandHandlers() as $commandHandler) {
                if (!isset($row[$commandHandler->getName()])) {
                    $row[$commandHandler->getName()] = [];
                }

                $row[$commandHandler->getName()][] = $value;
            }
        }

        foreach ($row as $k => $v) {
            $row[$k] = implode('; ', $v);
        }

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram bot\'s commands have been removed', $bot->getUsername()));

        return Command::SUCCESS;
    }
}