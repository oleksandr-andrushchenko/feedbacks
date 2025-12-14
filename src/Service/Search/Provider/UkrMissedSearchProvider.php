<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrMissed\UkrMissedDisappearedPersons;
use App\Model\Search\UkrMissed\UkrMissedPerson;
use App\Model\Search\UkrMissed\UkrMissedWantedPersons;
use App\Service\HttpRequester;
use App\Service\Intl\Ukr\UkrPersonNameProvider;
use DateTimeImmutable;

/**
 * @see https://www.npu.gov.ua/rozshuk-zniklih-gromadyan?&surname=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE&apiType=0
 * @see https://www.npu.gov.ua/api/integration/disappeared-persons-by-constituent-data?&surname=%D0%90%D0%9D%D0%94%D0%A0%D0%A3%D0%A9%D0%95%D0%9D%D0%9A%D0%9E&apiType=0
 * @see https://www.npu.gov.ua/api/integration/wanted-persons-by-constituent-data?&surname=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE&apiType=1&page=1
 * @todo implement https://www.npu.gov.ua/api/integration/disappeared-persons-ouf-by-constituent-data?&surname=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE
 * @todo implement real browsers like selenium (coz of claudflare)
 */
class UkrMissedSearchProvider extends SearchProvider implements SearchProviderInterface
{
    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly HttpRequester $httpRequester,
        private readonly UkrPersonNameProvider $ukrPersonNameProvider,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukr_missed;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type !== SearchTermType::person_name) {
            return false;
        }

        if (
            empty($this->ukrPersonNameProvider->getPersonNames($term, withLast: true))
            && empty($this->ukrPersonNameProvider->getPersonNames($term, withMinComponents: 2))
        ) {
            return false;
        }

        if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $term) !== 1) {
            return false;
        }

        return true;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchDisappearedPersons($term),
            $this->searchWantedPersons($term),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return true;
    }

    private function searchDisappearedPersons(string $name): ?UkrMissedDisappearedPersons
    {
        $persons = $this->searchPersons($name, true);

        if ($persons === null) {
            return null;
        }

        return new UkrMissedDisappearedPersons($persons);
    }

    private function searchWantedPersons(string $name): ?UkrMissedWantedPersons
    {
        $persons = $this->searchPersons($name, false);

        if ($persons === null) {
            return null;
        }

        return new UkrMissedWantedPersons($persons);
    }

    private function searchPersons(string $name, bool $disappeared): ?array
    {
        foreach ($this->ukrPersonNameProvider->getPersonNames($name) as $personName) {
            $query = array_merge([
                'surname' => $personName->getLast(),
                'name' => $personName->getFirst(),
                'middlename' => $personName->getPatronymic(),
                'apiType' => '0',
                'page' => '1',
            ]);

            if ($disappeared) {
                $url = 'https://www.npu.gov.ua/api/integration/disappeared-persons-by-constituent-data';
            } else {
                $url = 'https://www.npu.gov.ua/api/integration/wanted-persons-by-constituent-data';
            }

            $url .= '?' . http_build_query($query);

            $data = $this->httpRequester->requestHttp('GET', $url, user: true, array: true);

            $items = [];

            foreach ($data['items'] as $item) {
                $items[] = new UkrMissedPerson(
                    surname: isset($item['person'], $item['person']['surname']) ? $item['person']['surname'] : null,
                    name: isset($item['person'], $item['person']['name']) ? $item['person']['name'] : null,
                    middleName: isset($item['person'], $item['person']['middlename']) ? $item['person']['middlename'] : null,
                    sex: isset($item['person'], $item['person']['sex']) ? $item['person']['sex'] : null,
                    birthday: isset($item['person'], $item['person']['birthday']) ? DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $item['person']['birthday'])->setTime(0, 0) : null,
                    photo: isset($item['person'], $item['person']['photo']) ? $item['person']['photo'] : null,
                    category: isset($item['wanted'], $item['wanted']['category']) ? $item['wanted']['category'] : null,
                    disappeared: $disappeared,
                    articles: isset($item['wanted'], $item['wanted']['articles']) ? $item['wanted']['articles'] : null,
                    date: isset($item['wanted'], $item['wanted']['datetime']) ? DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $item['wanted']['datetime'])->setTime(0, 0) : null,
                    organ: isset($item['wanted'], $item['wanted']['organ']) ? $item['wanted']['organ'] : null,
                    precaution: isset($item['wanted'], $item['wanted']['precaution']) ? $item['wanted']['precaution'] : null,
                    address: implode(', ', array_filter([
                        isset($item['address'], $item['address']['country']) ? $item['address']['country'] : null,
                        isset($item['address'], $item['address']['region']) ? $item['address']['region'] : null,
                        isset($item['address'], $item['address']['district']) ? $item['address']['district'] : null,
                        isset($item['address'], $item['address']['locality']) ? $item['address']['locality'] : null,
                    ])),
                );
            }

            if (count($items) > 0) {
                return $items;
            }
        }

        return null;
    }
}
