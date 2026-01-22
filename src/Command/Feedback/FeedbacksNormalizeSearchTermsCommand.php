<?php

declare(strict_types=1);

namespace App\Command\Feedback;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\SearchTermRepository;
use App\Service\Doctrine\DryRunner;
use App\Service\Feedback\SearchTerm\PhoneNumberSearchTermTextNormalizer;
use App\Service\ORM\EntityManager;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class FeedbacksNormalizeSearchTermsCommand extends Command
{
    public function __construct(
        private readonly SearchTermRepository $searchTermRepository,
        private readonly PhoneNumberSearchTermTextNormalizer $phoneNumberSearchTermTextNormalizer,
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
        $this->addOption('date', mode: InputOption::VALUE_REQUIRED, description: 'Date in mm/dd/yyyy format')
            ->addOption('phones', mode: InputOption::VALUE_NONE, description: 'Normalize phone number types')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Country code (-s)')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Normalize feedback search terms')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('date') === null) {
            $searchTerms = $this->searchTermRepository->findAll();
        } else {
            $date = $input->getOption('date');

            $dateTime = DateTimeImmutable::createFromFormat('m/d/Y', $date);
            $from = $dateTime->setTime(0, 0);
            $to = $from->modify('+1 day');

            $searchTerms = $this->searchTermRepository->findByPeriod($from, $to);
        }

        $count = count($searchTerms);

        if ($count === 0) {
            $io->note('Nothing to normalize');

            return Command::SUCCESS;
        }

        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(
                    sprintf(
                        'There are %d feedback search terms were found%s. Are you sure you want to normalize them?',
                        $count,
                        isset($date) ? ' for %s date' : ''
                    ),
                    true
                )
            );

            if (!$confirmed) {
                $io->warning('Normalization has been cancelled');

                return Command::SUCCESS;
            }
        }

        $phones = $input->getOption('phones');
        $countryCodes = $input->getOption('country');

        $context = [
            'country_codes' => $countryCodes,
        ];

        $func = fn () => $this->normalize($searchTerms, phones: $phones, context: $context, io: $io);

        if ($dryRun) {
            $count = $this->dryRunner->dryRun($func, readUncommitted: true);
        } else {
            $count = $func();
            $this->entityManager->flush();
        }

        $io->success(sprintf('%d Feedback search terms have been normalized', $count));

        return Command::SUCCESS;
    }

    /**
     * @param SearchTerm[] $searchTerms
     * @param bool $phones
     * @param array $context
     * @param SymfonyStyle|null $io
     * @return int
     */
    private function normalize(iterable $searchTerms, bool $phones = false, array $context = [], SymfonyStyle $io = null): int
    {
        $count = 0;

        foreach ($searchTerms as $searchTerm) {
            if ($searchTerm->getType() === SearchTermType::phone_number && $phones) {
                $normalizedText = $this->phoneNumberSearchTermTextNormalizer
                    ->normalizePhoneNumberSearchTermText($searchTerm->getNormalizedText(), $context['country_codes'] ?? [])
                ;
            } else {
                continue;
            }

            if ($searchTerm->getNormalizedText() === $normalizedText) {
                continue;
            }

            $io->note(
                sprintf('%s [ %s ] => %s', $searchTerm->getNormalizedText(), $searchTerm->getType()->name, $normalizedText)
            );

            $searchTerm->setNormalizedText($normalizedText);

            $count++;
        }

        return $count;
    }
}