<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\Telegram\Bot\TelegramBotGroupNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\TelegramBotInfoProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotShowAllCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotInfoProvider $telegramBotInfoProvider,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group name')
            ->addOption('full', mode: InputOption::VALUE_NONE, description: 'Whether to show all information')
            ->setDescription('Show all telegram bots info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $groupName = $input->getOption('group');
        $group = null;

        if ($groupName !== null) {
            $group = TelegramBotGroupName::fromName($groupName);

            if ($group === null) {
                throw new TelegramBotGroupNotFoundException($groupName);
            }
        }

        $full = $input->getOption('full');

        $bots = $this->telegramBotRepository->findAll();

        $table = [];
        $index = 0;

        foreach ($bots as $bot) {
            if ($group !== null && $bot->getGroup() !== $group) {
                continue;
            }

            $table[] = array_merge(
                [
                    '#' => $index + 1,
                ],
                $this->telegramBotInfoProvider->getTelegramBotInfo($bot, $full)
            );

            $index++;
        }

        if (count($table) === 0) {
            $io->success('No telegram bots have been found');
        } else {
            $io->createTable()
                ->setHeaders(array_keys($table[0]))
                ->setRows($table)
                ->setVertical($full)
                ->render()
            ;

            $io->newLine();
            $io->success('Telegram bots info have been shown');
        }

        return Command::SUCCESS;
    }
}