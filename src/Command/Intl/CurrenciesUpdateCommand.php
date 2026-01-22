<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Service\Intl\CurrenciesProviderInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CurrenciesUpdateCommand extends Command
{
    public function __construct(
        private readonly CurrenciesProviderInterface $currenciesProvider,
        private readonly NormalizerInterface $normalizer,
        private readonly string $targetFile,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Update latest currencies');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $currencies = $this->currenciesProvider->getCurrencies();

        if ($currencies === null) {
            throw new RuntimeException('Unable to fetch currencies');
        }

        $data = [];
        foreach ($currencies as $currency) {
            $data[$currency->getCode()] = $this->normalizer->normalize($currency, format: 'internal');
        }

        $json = json_encode($data);
        $written = file_put_contents($this->targetFile, $json);

        if ($written === false) {
            throw new RuntimeException('Unable to write currencies');
        }

        $io->note($json);

        $io->newLine();
        $io->success('Currencies have been updated');

        return Command::SUCCESS;
    }
}