<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\Clarity\ClarityEdr;
use App\Model\Search\Clarity\ClarityEdrs;
use App\Model\Search\Clarity\ClarityPerson;
use App\Model\Search\Clarity\ClarityPersonCourt;
use App\Model\Search\Clarity\ClarityPersonCourts;
use App\Model\Search\Clarity\ClarityPersonDebtor;
use App\Model\Search\Clarity\ClarityPersonDebtors;
use App\Model\Search\Clarity\ClarityPersonDeclaration;
use App\Model\Search\Clarity\ClarityPersonDeclarations;
use App\Model\Search\Clarity\ClarityPersonEdr;
use App\Model\Search\Clarity\ClarityPersonEdrs;
use App\Model\Search\Clarity\ClarityPersonEnforcement;
use App\Model\Search\Clarity\ClarityPersonEnforcements;
use App\Model\Search\Clarity\ClarityPersons;
use App\Model\Search\Clarity\ClarityPersonSecurities;
use App\Model\Search\Clarity\ClarityPersonSecurity;
use App\Service\CrawlerProvider;
use App\Service\Intl\Ukr\UkrPersonNameProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://clarity-project.info/persons
 * @see https://clarity-project.info/edrs
 * @see https://clarity-project.info/edr/2762811968/history/prozorro
 * @see https://clarity-project.info/edr/2762811968/persons
 */
class ClaritySearchProvider extends SearchProvider implements SearchProviderInterface
{
    public const URL = 'https://clarity-project.info';

    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly CrawlerProvider $crawlerProvider,
        private readonly UkrPersonNameProvider $ukrPersonNameProvider,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::clarity;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        if ($type === SearchTermType::person_name) {
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

        if ($type === SearchTermType::organization_name) {
            if (preg_match('/^[\p{Cyrillic}\s]+$/ui', $term) !== 1) {
                return false;
            }

            return true;
        }

        if ($type === SearchTermType::tax_number) {
            if (!is_numeric($term)) {
                return false;
            }

            if (strlen($term) !== 8) {
                return false;
            }

            return true;
        }

//        if ($type === SearchTermType::phone_number) {
//            if (!str_starts_with($term, '380')) {
//                return false;
//            }
//
//            return true;
//        }

        return false;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type === SearchTermType::person_name) {
            $personNames = $this->ukrPersonNameProvider->getPersonNames($term, withMinComponents: 3);

            if (count($personNames) === 1) {
                $name = $personNames[0]->getFormatted();
                $url = 'https://clarity-project.info/person/' . md5(mb_strtoupper($name));
                $referer = 'https://clarity-project.info/persons?search=' . urlencode($name);
                $records = $this->searchProviderCompose->tryCatch(fn () => $this->searchPersonRecords($url, $referer), [], [404]);
                $records = array_values(array_filter($records));

                if (!empty($records)) {
                    return $records;
                }

                sleep(2);
            }

            /** @var ClarityPersons $record */
            $record = $this->searchProviderCompose->tryCatch(fn () => $this->searchPersons($term), null);

            if ($record === null) {
                return [];
            }

            if (count($record->getItems()) === 1) {
                sleep(2);
                $url = $record->getItems()[0]->getHref();
                $referer = 'https://clarity-project.info/persons?search=' . urlencode($term);

                return $this->searchProviderCompose->tryCatch(fn () => $this->searchPersonRecords($url, $referer), []);
            }

            return [
                $record,
            ];
        }

        // todo parse edr page if single result (implemente the same as for persons made)
        return [
            $this->searchProviderCompose->tryCatch(fn () => $this->searchEdrs($term), null),
        ];
    }

    private function searchPersonRecords(string $url, string $referer = null): array
    {
        return [
            $this->searchPersonSecurity($url, $referer),
            $this->searchPersonCourts($url, $referer),
            $this->searchPersonDebtors($url, $referer),
            $this->searchPersonEnforcements($url, $referer),
            $this->searchPersonEdrs($url, $referer),
            $this->searchPersonDeclarations($url, $referer),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return true;
    }

    private function searchPersons(string $name): ?ClarityPersons
    {
        $url = '/persons?search=' . urlencode($name);
        $headers = [
            'Referer' => 'https://clarity-project.info/persons',
        ];
        $crawler = $this->crawlerProvider->getCrawler('GET', $url, base: self::URL, headers: $headers, user: true);

        $items = $crawler->filter('.results-wrap .item')->each(static function (Crawler $item): ?ClarityPerson {
            $a = $item->filter('a');

            if ($a->count() === 0) {
                return null;
            }

            $name = trim($a->eq(0)->text());

            if (empty($name)) {
                return null;
            }

            $href = trim($a->eq(0)->attr('href') ?? '');
            $href = empty($href) ? null : (self::URL . $href);

            return new ClarityPerson(
                $name,
                href: empty($href) ? null : $href,
                count: empty($count) ? null : $count
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersons($items);
    }

    private function searchPersonEdrs(string $url, string $referer = null): ?ClarityPersonEdrs
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        // todo: replace with https://clarity-project.info/edrs/?search=%name% (this variant holds addresses for fops)
        // todo: process @mainEntity json

        $table = $crawler->filter('[data-id="edrs"] table')->eq(0);
        $header = [];
        $tr = $table->children('tr')->eq(0);
        $tr->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'Назва')) {
            return null;
        }

        $ids = [];

        $items = $table->children('tr.item')->each(static function (Crawler $tr) use ($header, &$ids): ?ClarityPersonEdr {
            $id = $tr->attr('data-id');

            if (in_array($id, $ids, true)) {
                return null;
            }

            $ids[] = $id;

            $tds = $tr->filter('td');

            $nameEl = $tds->eq(0);

            if ($nameEl->count() === 0) {
                return null;
            }

            $active = str_contains(trim($nameEl->text()), 'Зареєстровано');

            $toRemove = $tds->eq(0)->children()->last()->getNode(0);
            $toRemove->parentNode->removeChild($toRemove);

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return null;
            }

            $hrefEl = $tr->filter('a');

            if ($hrefEl->count() !== 0) {
                $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : (self::URL . $href);
            }

            if (isset($header[1]) && str_contains($header[1], 'ЄДРПОУ') && $tds->eq(1)->count() !== 0) {
                $number = preg_replace('/[^0-9]/', '', trim($tds->eq(1)->text()));
            }

            if (isset($header[2]) && $tds->eq(2)->count() !== 0) {
                $address = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && $tds->eq(3)->count() !== 0) {
                $type = trim($tds->eq(3)->text());
            }

            return new ClarityPersonEdr(
                $name,
                type: empty($type) ? null : $type,
                href: empty($href) ? null : $href,
                number: empty($number) ? null : $number,
                active: $active,
                address: empty($address) ? null : $address
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonEdrs($items);
    }

    private function searchPersonCourts(string $url, string $referer = null): ?ClarityPersonCourts
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        $table = $crawler->filter('[data-id="court-involved"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'справи')) {
            return null;
        }

        $items = $table->filter('tr.item')->each(static function (Crawler $tr) use ($header): ?ClarityPersonCourt {
            $tds = $tr->filter('td');

            if ($tds->count() === 1) {
                return null;
            }

            $number = trim($tds->eq(0)->text());

            if (empty($number)) {
                return null;
            }

            if (isset($header[1]) && str_contains($header[1], 'Стан') && $tds->eq(1)->count() !== 0) {
                $state = trim($tds->eq(1)->text());
            }

            if (isset($header[2]) && str_contains($header[2], 'Сторона') && $tds->eq(2)->count() !== 0) {
                $side = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Опис') && $tds->eq(3)->count() !== 0) {
                $desc = trim($tds->eq(3)->text());
            }

            if (isset($header[4]) && str_contains($header[4], 'Суд') && $tds->eq(4)->count() !== 0) {
                $place = trim($tds->eq(4)->text());
            }

            return new ClarityPersonCourt(
                $number,
                state: empty($state) ? null : $state,
                side: empty($side) ? null : $side,
                desc: empty($desc) ? null : $desc,
                place: empty($place) ? null : $place
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonCourts($items);
    }

    private function searchPersonDebtors(string $url, string $referer = null): ?ClarityPersonDebtors
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        $table = $crawler->filter('[data-id="debtors"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'ПІБ')) {
            return null;
        }

        $items = $table->filter('tr.item')->each(static function (Crawler $tr) use ($header): ?ClarityPersonDebtor {
            $tds = $tr->filter('td');

            if ($tds->count() === 1) {
                return null;
            }

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return null;
            }

            if (isset($header[1]) && str_contains($header[1], 'народження') && $tds->eq(1)->count() !== 0) {
                $bornAt = trim($tds->eq(1)->text());
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Запис') && $tds->eq(2)->count() !== 0) {
                $category = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Актуально') && $tds->eq(3)->count() !== 0) {
                $actualAt = trim($tds->eq(3)->text());
                $actualAt = empty($actualAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $actualAt)->setTime(0, 0);
                $actualAt = $actualAt === false ? null : $actualAt;
            }

            return new ClarityPersonDebtor(
                $name,
                bornAt: empty($bornAt) ? null : $bornAt,
                category: empty($category) ? null : $category,
                actualAt: empty($actualAt) ? null : $actualAt,
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonDebtors($items);
    }

    private function searchPersonSecurity(string $url, string $referer = null): ?ClarityPersonSecurities
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        $table = $crawler->filter('[data-id="security"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'ПІБ')) {
            return null;
        }

        $items = $table->filter('tr.item')->each(static function (Crawler $tr) use ($header): ?ClarityPersonSecurity {
            $tds = $tr->filter('td');

            if ($tds->count() === 1) {
                return null;
            }

            $name = trim($tds->eq(0)->text());

            if (empty($name)) {
                return null;
            }

            if (isset($header[1]) && str_contains($header[1], 'народження') && $tds->eq(1)->count() !== 0) {
                $bornAt = trim($tds->eq(1)->text());
                $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                $bornAt = $bornAt === false ? null : $bornAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Категорія') && $tds->eq(2)->count() !== 0) {
                $category = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Регіон') && $tds->eq(3)->count() !== 0) {
                $region = trim($tds->eq(3)->text());
            }

            if (isset($header[4]) && str_contains($header[4], 'зникнення') && $tds->eq(4)->count() !== 0) {
                if ($tds->eq(4)->filter('.small')->count() !== 0) {
                    $archive = trim($tds->eq(4)->filter('.small')->text());
                    $archive = empty($archive) ? null : str_contains($archive, 'архівна');
                }

                if (!empty($tds->eq(4)->getNode(0)?->firstChild?->textContent)) {
                    $absentAt = trim($tds->eq(4)->getNode(0)->firstChild->textContent ?? '');
                    $absentAt = empty($absentAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $absentAt)->setTime(0, 0);
                    $absentAt = $absentAt === false ? null : $absentAt;
                }
            }

            $accusation = null;
            $precaution = null;

            $nextTr = $tr->nextAll();

            if ($nextTr->matches('.table-collapse-details')) {
                $trs = $nextTr->filter('tr');
                if ($trs->eq(5)->filter('td')->count() !== 0 && str_contains($trs->eq(5)->filter('td')->eq(0)->text(), 'Звинувачення')) {
                    $accusation = trim($trs->eq(5)->filter('td')->eq(1)->text());
                }
                if ($trs->eq(6)->filter('td')->count() !== 0 && str_contains($trs->eq(6)->filter('td')->eq(0)->text(), 'Запобіжний')) {
                    $precaution = trim($trs->eq(6)->filter('td')->eq(1)->text());
                }
            }

            return new ClarityPersonSecurity(
                $name,
                bornAt: empty($bornAt) ? null : $bornAt,
                category: empty($category) ? null : $category,
                region: empty($region) ? null : $region,
                absentAt: empty($absentAt) ? null : $absentAt,
                archive: $archive ?? null,
                accusation: empty($accusation) ? null : $accusation,
                precaution: empty($precaution) ? null : $precaution
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonSecurities($items);
    }

    private function searchPersonEnforcements(string $url, string $referer = null): ?ClarityPersonEnforcements
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        $table = $crawler->filter('[data-id="enforcements"]');
        $header = [];
        $table->filter('tr')->eq(0)->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'в/п')) {
            return null;
        }

        $items = $table->filter('tr.item')->each(static function (Crawler $tr) use ($header): ?ClarityPersonEnforcement {
            $tds = $tr->filter('td');

            if ($tds->count() === 1) {
                return null;
            }

            $number = trim($tds->eq(0)->text());

            if (empty($number)) {
                return null;
            }

            if (isset($header[1]) && str_contains($header[1], 'відкриття') && $tds->eq(1)->count() !== 0) {
                $openedAt = trim($tds->eq(1)->text());
                $openedAt = empty($openedAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $openedAt)->setTime(0, 0);
                $openedAt = $openedAt === false ? null : $openedAt;
            }

            if (isset($header[2]) && str_contains($header[2], 'Стягувач') && $tds->eq(2)->count() !== 0) {
                $collector = trim($tds->eq(2)->text());
            }

            if (isset($header[3]) && str_contains($header[3], 'Боржник') && $tds->eq(3)->count() !== 0) {
                $debtor = trim($tds->eq(3)->text());

                if ($tds->count() === count($header) + 1) {
                    if ($tds->eq(4)->count() !== 0) {
                        $bornAt = trim($tds->eq(4)->text());
                        $peaces = explode(' ', $bornAt);
                        $bornAt = $peaces[count($peaces) - 1];
                        $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                        $bornAt = $bornAt === false ? null : $bornAt;
                    }
                }
            }

            if (isset($header[4]) && str_contains($header[4], 'Стан') && $tds->eq($tds->count() - 1)->count() !== 0) {
                $state = trim($tds->eq($tds->count() - 1)?->text());
            }

            return new ClarityPersonEnforcement(
                $number,
                openedAt: empty($openedAt) ? null : $openedAt,
                collector: empty($collector) ? null : $collector,
                debtor: empty($debtor) ? null : $debtor,
                bornAt: empty($bornAt) ? null : $bornAt,
                state: empty($state) ? null : $state
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonEnforcements($items);
    }

    private function searchPersonDeclarations(string $url, string $referer = null): ?ClarityPersonDeclarations
    {
        $crawler = $this->getPersonCrawler($url, $referer);

        $table = $crawler->filter('[data-id="declarations"] table')->eq(0);
        $header = [];
        $tr = $table->children('tr')->eq(0);
        $tr->filter('th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'Рік')) {
            return null;
        }

        if (!isset($header[1]) || !str_contains($header[1], 'ПІБ')) {
            return null;
        }

        if (!isset($header[2]) || !str_contains($header[2], 'Посада')) {
            return null;
        }

        $items = $table->children('tr.item')->each(static function (Crawler $tr) use ($header): ?ClarityPersonDeclaration {
            $tds = $tr->filter('td');

            if (isset($header[1]) && $tds->eq(1)->count() !== 0) {
                $name = trim($tds->eq(1)->text());

                $hrefEl = $tds->eq(1)->filter('a');

                if ($hrefEl->count() !== 0) {
                    $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                    $href = empty($href) ? null : (self::URL . $href);
                }
            }

            if (empty($name)) {
                return null;
            }

            if (isset($header[0]) && $tds->count() !== 0) {
                $year = trim($tds->eq(0)->text());
            }

            if (isset($header[2]) && $tds->eq(2)->count() !== 0) {
                $position = trim($tds->eq(2)->text());
            }

            return new ClarityPersonDeclaration(
                $name,
                href: empty($href) ? null : $href,
                year: empty($year) ? null : $year,
                position: empty($position) ? null : $position
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityPersonDeclarations($items);
    }

    private function searchEdrs(string $name): ?ClarityEdrs
    {
        $url = '/edrs?search=' . urlencode($name);
        $headers = [
            'Referer' => 'https://clarity-project.info/edrs',
        ];
        $crawler = $this->crawlerProvider->getCrawler('GET', $url, base: self::URL, headers: $headers, user: true);

        $items = $crawler->filter('.results-wrap .item')->each(static function (Crawler $item): ?ClarityEdr {
            $nameEl = $item->filter('h5');

            if ($nameEl->count() === 0) {
                return null;
            }

            $name = trim($nameEl->text());

            if (empty($name)) {
                return null;
            }

            $hrefEl = $nameEl->filter('a');

            if ($hrefEl->count() !== 0) {
                $href = trim($hrefEl->eq(0)->attr('href') ?? '');
                $href = empty($href) ? null : (self::URL . $href);
            }

            $numberEl = $item->filter('.small');

            if ($numberEl->count() !== 0) {
                preg_match('/[0-9]{8}/', $numberEl->text(), $m);
                $number = isset($m, $m[0]) ? $m[0] : null;
            }

            $addressEl = $item->filter('.address');

            if ($addressEl->count() !== 0) {
                $address = trim($addressEl->text());
            }

            return new ClarityEdr(
                $name,
                type: empty($type) ? null : $type,
                href: empty($href) ? null : $href,
                number: empty($number) ? null : $number,
                active: $active ?? null,
                address: empty($address) ? null : $address
            );
        });

        $items = array_values(array_filter($items));

        return count($items) === 0 ? null : new ClarityEdrs($items);
    }

    private function getPersonCrawler(string $url, string $referer = null): Crawler
    {
        $headers = $referer === null ? ['Referer' => $referer] : null;
        $base = str_starts_with($url, self::URL) ? null : self::URL;

        return $this->crawlerProvider->getCrawler('GET', $url, base: $base, headers: $headers, user: true);
    }
}
