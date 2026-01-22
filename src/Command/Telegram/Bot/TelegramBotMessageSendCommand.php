<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotMessageSendCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addArgument('chat', InputArgument::REQUIRED, 'Target telegram chat id')
            ->addArgument('text', InputArgument::REQUIRED, 'Message to send')
            ->setDescription('Send message to chat from Telegram bot')
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

        $chatId = $input->getArgument('chat');
        $chatId = is_numeric($chatId) ? $chatId : ('@' . $chatId);
        $text = $input->getArgument('text');

        $this->telegramBotMessageSender->sendTelegramMessage($bot, $chatId, $text);

        $io->success('Message has been successfully sent');

        return Command::SUCCESS;
    }
}