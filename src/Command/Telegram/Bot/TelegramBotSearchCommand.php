<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\Search\Searcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * @see TelegramBotSearchCommand
 */
class TelegramBotSearchCommand extends Command
{
    public function __construct(
        private readonly Searcher $searcher,
        private readonly LocaleSwitcher $localeSwitcher
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('term', InputArgument::REQUIRED, 'Search term')
            ->addArgument('type', InputArgument::REQUIRED, 'Search term type')
            ->addOption('provider', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Provider (-s)')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Context country')
            ->addOption('locale', mode: InputOption::VALUE_REQUIRED, description: 'Locale')
            ->addOption('full', mode: InputOption::VALUE_NONE, description: 'Context country')
            ->setDescription('Search with Telegram viewer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $term = $input->getArgument('term');
        $termType = SearchTermType::fromName($input->getArgument('type'));

        $searchTerm = new SearchTerm($term, $term, $termType);
        $render = static fn (string $message) => $io->text($message);
        $context = [
            'countryCode' => $input->getOption('country'),
            'full' => $input->getOption('full'),
        ];
        $locale = $input->getOption('locale');

        if ($locale !== null) {
            $this->localeSwitcher->setLocale($locale);
        }

        $providers = array_map(
            static fn (string $provider): SearchProviderName => SearchProviderName::fromName($provider),
            $input->getOption('provider')
        );

        $this->searcher->search($searchTerm, $render, $context, $providers);

        $io->success('Search has been completed');

        return Command::SUCCESS;
    }
}