<?php

declare(strict_types=1);

namespace App\Service\Intl;

use App\Entity\Intl\Level1Region;
use App\Exception\AddressGeocodeFailedException;
use App\Exception\TimezoneGeocodeFailedException;
use App\Model\Intl\Country;
use App\Model\Location;
use App\Repository\Intl\Level1RegionRepository;
use App\Service\AddressGeocoderInterface;
use App\Service\TimezoneGeocoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Level1RegionProvider
{
    public function __construct(
        private readonly AddressGeocoderInterface $addressGeocoder,
        private readonly TimezoneGeocoderInterface $timezoneGeocoder,
        private readonly Level1RegionUpserter $level1RegionUpserter,
        private readonly Level1RegionRepository $level1RegionRepository,
        private readonly TranslatorInterface $translator,
        private readonly string $sourceFile,
        private readonly DenormalizerInterface $denormalizer,
    )
    {
    }

    /**
     * @param Location $location
     * @return Level1Region
     * @throws AddressGeocodeFailedException
     * @throws TimezoneGeocodeFailedException
     */
    public function getLevel1RegionByLocation(Location $location): Level1Region
    {
        $address = $this->addressGeocoder->geocodeAddress($location);
        $level1Region = $this->level1RegionUpserter->upsertLevel1RegionByAddress($address);

        if ($level1Region->getTimezone() === null) {
            $timezone = $this->timezoneGeocoder->geocodeTimezone($location);
            $level1Region->setTimezone($timezone);
        }

        return $level1Region;
    }

    public function getLevel1RegionByCountryAndName(string $countryCode, string $name, string $timezone = null): ?Level1Region
    {
        $level1Region = $this->level1RegionUpserter->upsertLevel1RegionByCountryAndName($countryCode, $name);

        if ($level1Region->getTimezone() === null && $timezone !== null) {
            $level1Region->setTimezone($timezone);
        }

        return $level1Region;
    }

    /**
     * @param Country $country
     * @return Level1Region[]
     */
    public function getLevel1Regions(Country $country): array
    {
        if ($country->level1RegionsDumped()) {
            return array_map(
                fn (array $record): Level1Region => $this->denormalize($record),
                array_values($this->getNormalizedData($country->getCode()))
            );
        }

        return $this->level1RegionRepository->findByCountry($country->getCode());
    }

    public function getLevel1RegionNameById(Country $country, string $level1RegionId): ?string
    {
        if ($country->level1RegionsDumped()) {
            $normalizedLevel1Region = $this->getNormalizedData($country->getCode())[$level1RegionId] ?? null;

            if ($normalizedLevel1Region === null) {
                return null;
            }

            $level1Region = $this->denormalize($normalizedLevel1Region);
        } else {
            $level1Region = $this->level1RegionRepository->find($level1RegionId);
        }

        if ($level1Region === null) {
            return null;
        }

        return $this->getLevel1RegionName($level1Region);
    }

    public function getLevel1RegionName(Level1Region $level1Region, string $localeCode = null): string
    {
        return $this->translator->trans(
            $level1Region->getName(),
            domain: sprintf('level_1_region.%s', $level1Region->getCountryCode()),
            locale: $localeCode
        );
    }

    public function getLevel1Region(string $id): Level1Region
    {
        return $this->level1RegionRepository->find($id);
    }

    private function denormalize(array $record): Level1Region
    {
        return $this->denormalizer->denormalize($record, Level1Region::class, format: 'internal');
    }

    private function getNormalizedData(string $countryCode): array
    {
        static $data = [];

        if (!isset($data[$countryCode])) {
            $content = file_get_contents(str_replace('{country}', $countryCode, $this->sourceFile));
            $data[$countryCode] = json_decode($content, true);
        }

        return $data[$countryCode];
    }
}