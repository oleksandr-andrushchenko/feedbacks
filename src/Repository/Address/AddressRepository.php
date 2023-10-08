<?php

declare(strict_types=1);

namespace App\Repository\Address;

use App\Entity\Address\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 *
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findOneByAddress(Address $address): ?Address
    {
        $addresses = $this->findBy([
            'locality' => $address->getLocality(),
        ]);

        foreach ($addresses as $existingAddress) {
            if ($existingAddress->getCountryCode() !== $address->getCountryCode()) {
                continue;
            }

            if ($existingAddress->getRegion1() !== $address->getRegion1()) {
                continue;
            }

            if ($existingAddress->getRegion2() !== $address->getRegion2()) {
                continue;
            }

            return $existingAddress;
        }

        return null;
    }

    public function findOneByAddressComponents(
        string $country,
        string $region1,
        string $region2,
        string $locality
    ): ?Address
    {
        $addresses = $this->findBy([
            'locality' => $locality,
        ]);

        foreach ($addresses as $existingAddress) {
            if ($existingAddress->getCountryCode() !== $country) {
                continue;
            }

            if ($existingAddress->getRegion1() !== $region1) {
                continue;
            }

            if ($existingAddress->getRegion2() !== $region2) {
                continue;
            }

            return $existingAddress;
        }

        return null;
    }
}
