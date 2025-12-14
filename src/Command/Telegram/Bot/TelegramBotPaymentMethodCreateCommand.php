<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Exception\Telegram\Bot\Payment\TelegramBotPaymentMethodNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentMethodCreator;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentMethodInfoProvider;
use App\Transfer\Telegram\TelegramBotPaymentMethodTransfer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotPaymentMethodCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotPaymentMethodCreator $telegramBotPaymentMethodCreator,
        private readonly EntityManager $entityManager,
        private readonly TelegramBotPaymentMethodInfoProvider $telegramBotPaymentMethodInfoProvider,
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
            ->addArgument('name', InputArgument::REQUIRED, 'Payment Method Name')
            ->addArgument('token', InputArgument::REQUIRED, 'Payment method Token')
            ->addArgument('currencies', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Currencies')
            ->setDescription('Create telegram bot payment method')
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

        $paymentMethodTransfer = new TelegramBotPaymentMethodTransfer(
            $bot,
            $name,
            $input->getArgument('token'),
            $input->getArgument('currencies')
        );

        $paymentMethod = $this->telegramBotPaymentMethodCreator->createTelegramPaymentMethod($paymentMethodTransfer);

        $this->entityManager->flush();

        $row = $this->telegramBotPaymentMethodInfoProvider->getTelegramPaymentInfo($paymentMethod);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(
            sprintf(
                '"%s" Telegram bot\'s payment method has been added for "%s" Telegram bot',
                $paymentMethod->getName()->name,
                $bot->getUsername()
            )
        );

        return Command::SUCCESS;
    }
}