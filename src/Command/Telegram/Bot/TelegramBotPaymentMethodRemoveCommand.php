<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentMethodNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\ORM\EntityManager;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotPaymentMethodRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotPaymentMethodRepository $telegramBotPaymentMethodRepository,
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
        $this->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addArgument('name', InputArgument::REQUIRED, 'Payment Method Name')
            ->setDescription('Remove telegram bot payment method')
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

        $methodName = $input->getArgument('username');
        $name = TelegramBotPaymentMethodName::fromName($methodName);

        if ($name === null) {
            throw new TelegramBotPaymentMethodNotFoundException($methodName);
        }

        $paymentMethod = $this->telegramBotPaymentMethodRepository->findOneActiveByBotAndName($bot, $name);

        if ($paymentMethod === null) {
            throw new TelegramBotPaymentMethodNotFoundException($methodName);
        }

        $confirmed = $io->askQuestion(
            new ConfirmationQuestion(
                sprintf(
                    'Are you sure you want to remove "%s" telegram bot\'s "%s" payment method?',
                    $bot->getUsername(),
                    $paymentMethod->getName()->name
                ),
                true
            )
        );

        if (!$confirmed) {
            $io->warning(
                sprintf(
                    '"%s" telegram bot\'s "%s" payment method removing has been cancelled',
                    $bot->getUsername(),
                    $paymentMethod->getName()->name
                )
            );

            return Command::SUCCESS;
        }

        $paymentMethod->setDeletedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $io->success(
            sprintf(
                '"%s" Telegram bot\'s "%s" payment method has been removed',
                $bot->getUsername(),
                $paymentMethod->getName()->name
            )
        );

        return Command::SUCCESS;
    }
}