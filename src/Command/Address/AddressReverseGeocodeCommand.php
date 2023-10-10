<?php

declare(strict_types=1);

namespace App\Command\Address;

use App\Entity\Location;
use App\Service\Address\AddressInfoProvider;
use App\Service\Address\AddressProvider;
use App\Service\Doctrine\DryRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddressReverseGeocodeCommand extends Command
{
    public function __construct(
        private readonly AddressProvider $provider,
        private readonly AddressInfoProvider $infoProvider,
        private readonly DryRunner $dryRunner,
        private readonly EntityManagerInterface $entityManager,
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

        $func = fn () => $this->provider->getAddress($location);

        if ($dryRun) {
            $address = $this->dryRunner->dryRun($func);
        } else {
            $address = $func();
            $this->entityManager->flush();
        }

        $row = $this->infoProvider->getAddressInfo($address);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success('Address Location has been reverse-geocoded');

        return Command::SUCCESS;
    }
}