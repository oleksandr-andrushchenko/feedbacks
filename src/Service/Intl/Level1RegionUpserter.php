<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Level1Region;
use App\Model\Address\Address;
use App\Repository\Intl\Level1RegionRepository;
use App\Service\IdGenerator;
use App\Service\ORM\EntityManager;

class Level1RegionUpserter
{
    public function __construct(
        private readonly Level1RegionRepository $level1RegionRepository,
        private readonly EntityManager $entityManager,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    public function upsertLevel1RegionByAddress(Address $address): Level1Region
    {
        return $this->upsertLevel1RegionByCountryAndName(
            $address->getCountry(),
            $address->getAdministrativeAreaLevel1()
        );
    }

    public function upsertLevel1RegionByCountryAndName(string $countryCode, string $name): Level1Region
    {
        $countryCode = strtolower($countryCode);
        $level1Region = $this->level1RegionRepository->findOneByCountryAndName($countryCode, $name);

        if ($level1Region === null) {
            $level1Region = new Level1Region(
                $this->idGenerator->generateId(),
                $countryCode,
                $name,
            );
            $this->entityManager->persist($level1Region);
        }

        return $level1Region;
    }
}