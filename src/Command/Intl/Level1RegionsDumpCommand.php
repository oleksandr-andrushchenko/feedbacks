<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Exception\Intl\CountryNotFoundException;
use App\Model\Intl\Country;
use App\Repository\Intl\Level1RegionRepository;
use App\Serializer\Intl\CountryNormalizer;
use App\Service\Intl\CountryProvider;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Level1RegionsDumpCommand extends Command
{
    public function __construct(
        private readonly NormalizerInterface $countryNormalizer,
        private readonly NormalizerInterface $regionNormalizer,
        private readonly CountryProvider $countryProvider,
        private readonly Level1RegionRepository $level1RegionRepository,
        private readonly string $countriesTargetFile,
        private readonly string $regionsTargetFile,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->addArgument('country', InputArgument::REQUIRED, 'Country code')
            ->setDescription('Dump level 1 regions (Put/cache country regions in static json file)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $countryCode = $input->getArgument('country');
        $country = $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            throw new CountryNotFoundException($countryCode);
        }

        $regionsTargetFile = str_replace('{country}', $country->getCode(), $this->regionsTargetFile);

        if (!file_exists($this->countriesTargetFile)) {
            throw new RuntimeException(sprintf('%s file is not exists', $this->countriesTargetFile));
        }

        $this->dumpRegions($country, $regionsTargetFile, $io);
        $this->updateCountries($country, $this->countriesTargetFile, $io);

        $io->newLine();
        $io->success(sprintf('Level 1 regions have been dumped to %s', $regionsTargetFile));

        return Command::SUCCESS;
    }

    private function updateCountries(Country $country, string $targetFile, SymfonyStyle $io): void
    {
        $countryCode = $country->getCode();
        $countries = $this->countryProvider->getCountries();

        $data = [];
        foreach ($countries as $country) {
            $data[$country->getCode()] = $this->countryNormalizer->normalize($country, format: 'internal');

            if ($country->getCode() === $countryCode) {
                $data[$country->getCode()][CountryNormalizer::LEVEL_1_REGIONS_DUMPED_KEY] = true;
            }
        }

        $json = json_encode($data);
        $written = file_put_contents($targetFile, $json);

        if ($written === false) {
            throw new RuntimeException(sprintf('Unable to write countries into %s', $targetFile));
        }

        $io->note($json);
    }

    private function dumpRegions(Country $country, string $targetFile, SymfonyStyle $io): void
    {
        $countryCode = $country->getCode();
        $regions = $this->level1RegionRepository->findByCountry($countryCode);

        $data = [];
        foreach ($regions as $region) {
            $data[$region->getId()] = $this->regionNormalizer->normalize($region, format: 'internal');
        }

        $json = json_encode($data);
        $written = file_put_contents($targetFile, $json);

        if ($written === false) {
            throw new RuntimeException(sprintf('Unable to write level 1 regions into %s', $targetFile));
        }

        $io->note($json);
    }
}