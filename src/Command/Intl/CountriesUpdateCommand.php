<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Serializer\Intl\CountryNormalizer;
use App\Service\Intl\CountriesProviderInterface;
use App\Service\Intl\CountryTranslationsProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use RuntimeException;

class CountriesUpdateCommand extends Command
{
    public function __construct(
        private readonly CountriesProviderInterface $countriesProvider,
        private readonly NormalizerInterface $normalizer,
        private readonly string $targetFile,
        private readonly string $regionsTargetFile,
        private readonly CountryTranslationsProviderInterface $countryTranslationsProvider,
        private readonly string $translationTargetFile,
        private readonly array $supportedLocales,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->addOption('no-translations', mode: InputOption::VALUE_NONE, description: 'Whether to not update translations')
            ->setDescription('Update countries and country translations')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->updateCountries($io);

        if (!$input->getOption('no-translations')) {
            $this->updateCountryTranslations($io);
        }

        $io->newLine();
        $io->success('Countries have been updated');

        return Command::SUCCESS;
    }

    private function updateCountries(SymfonyStyle $io): void
    {
        $countries = $this->countriesProvider->getCountries();

        if ($countries === null) {
            throw new RuntimeException('Unable to fetch countries');
        }

        $data = [];
        foreach ($countries as $country) {
            $data[$country->getCode()] = $this->normalizer->normalize($country, format: 'internal');

            $regionsTargetFile = str_replace('{country}', $country->getCode(), $this->regionsTargetFile);

            if (file_exists($regionsTargetFile)) {
                $data[$country->getCode()][CountryNormalizer::LEVEL_1_REGIONS_DUMPED_KEY] = true;
            }
        }

        $json = json_encode($data);
        $written = file_put_contents($this->targetFile, $json);

        if ($written === false) {
            throw new RuntimeException('Unable to write countries');
        }

        $io->note($json);
    }

    private function updateCountryTranslations(SymfonyStyle $io): void
    {
        $translations = $this->countryTranslationsProvider->getCountryTranslations();

        if ($translations === null) {
            throw new RuntimeException('Unable to fetch country translations');
        }

        foreach ($translations as $locale => $data) {
            if (!isset($this->supportedLocales[$locale])) {
                continue;
            }

            $yaml = '';
            foreach ($data as $country => $translation) {
                $yaml .= sprintf("%s: %s\n", $country, $translation);
            }

            $written = file_put_contents(str_replace('{locale}', $locale, $this->translationTargetFile), $yaml);

            if ($written === false) {
                throw new RuntimeException(sprintf('Unable to write "%s" country translation', $locale));
            }

            $io->note(json_encode([
                'locale' => $locale,
                'translations' => array_keys($data),
            ]));
        }
    }
}