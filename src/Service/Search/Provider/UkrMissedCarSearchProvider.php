<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrMissedCar\UkrMissedCar;
use App\Service\CrawlerProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://wanted.mvs.gov.ua/searchtransport/?NOM=%D0%90%D0%900601%D0%92%D0%A2&NSH=&g-recaptcha-response=
 */
class UkrMissedCarSearchProvider extends SearchProvider implements SearchProviderInterface
{
    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly CrawlerProvider $crawlerProvider,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::ukr_missed_cars;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();

        if ($type !== SearchTermType::car_number) {
            return false;
        }

        return true;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchCars($term),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return true;
    }

    private function searchCars(string $number): ?array
    {
        $parameters = ['NOM' => $number];
        $url = 'https://wanted.mvs.gov.ua/searchtransport?' . http_build_query($parameters);
        $crawler = $this->crawlerProvider->getCrawler('GET', $url, user: true);

        $items = $crawler->filter('.cards-list > .card > .info-list')->each(static function (Crawler $item): ?UkrMissedCar {
            $listItems = $item->filter('.info-list-item');

            if ($listItems->count() === 0) {
                return null;
            }

            $map = [
                'Регіон' => 'region',
                'Державний знак' => 'carNumber',
                'МОДЕЛЬ' => 'model',
                'Номер шасі' => 'chassisNumber',
                'Номер кузова' => 'bodyNumber',
                'Колір' => 'color',
            ];
            $values = array_combine(array_values($map), array_fill(0, count($map), null));

            $listItems->each(static function (Crawler $listItem) use ($map, &$values): void {
                $divs = $listItem->children('div');

                if ($divs->count() < 2) {
                    return;
                }

                $labelEl = $divs->eq(0);
                $valueEl = $divs->eq(1);

                foreach ($map as $label => $key) {
                    if (str_contains($labelEl->text(), $label)) {
                        $values[$key] = $valueEl->text();
                    }
                }
            });

            $carNumber = $values['carNumber'];

            if (empty($carNumber)) {
                return null;
            }

            return new UkrMissedCar(
                $carNumber,
                region: empty($values['region']) ? null : $values['region'],
                model: empty($values['model']) ? null : $values['model'],
                chassisNumber: empty($values['chassisNumber']) ? null : $values['chassisNumber'],
                bodyNumber: empty($values['bodyNumber']) ? null : $values['bodyNumber'],
                color: empty($values['color']) ? null : $values['color'],
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return array_values($items);
        }

        return null;
    }
}
