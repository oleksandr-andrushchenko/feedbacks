<?php

declare(strict_types=1);

namespace App\Command\Address;

use App\Exception\AddressGeocodeFailedException;
use App\Exception\TimezoneGeocodeFailedException;
use App\Model\Location;
use App\Service\Address\AddressInfoProvider;
use App\Service\AddressGeocoderInterface;
use App\Service\Doctrine\DryRunner;
use App\Service\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddressReverseGeocodeCommand extends Command
{
    public function __construct(
        private readonly AddressGeocoderInterface $addressGeocoder,
        private readonly AddressInfoProvider $addressInfoProvider,
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
        $this
            ->addArgument('coordinates', InputArgument::REQUIRED, 'Latitude and Longitude (separated by comma)')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Address Reverse Geocode with Google Service')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $coordinates = $input->getArgument('coordinates');

        [$latitude, $longitude] = explode(',', $coordinates);

        $location = new Location($latitude, $longitude);
        $dryRun = $input->getOption('dry-run');

        try {
            $func = fn () => $this->addressGeocoder->geocodeAddress($location);

            if ($dryRun) {
                $address = $this->dryRunner->dryRun($func);
            } else {
                $address = $func();
                $this->entityManager->flush();
            }
        } catch (AddressGeocodeFailedException|TimezoneGeocodeFailedException $exception) {
            $io->warning(sprintf('Address Location has not been reverse-geocoded, content: %s', $exception->getContent()));

            return Command::SUCCESS;
        }

        $row = $this->addressInfoProvider->getAddressInfo($address);
        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->success('Address Location has been reverse-geocoded');

        return Command::SUCCESS;
    }
}