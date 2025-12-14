<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrWantedPerson\UkrWantedPerson;
use App\Model\Search\UkrWantedPerson\UkrWantedPersons;
use App\Service\CrawlerProvider;
use App\Service\Intl\Ukr\UkrPersonNameProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://wanted.mvs.gov.ua/searchperson/
 * @see https://wanted.mvs.gov.ua/searchperson/?PRUFM=%D0%90%D0%BD%D0%B4%D1%80%D1%83%D1%89%D0%B5%D0%BD%D0%BA%D0%BE&PRUIM=%D0%9E%D0%BB%D0%B5%D0%BA%D1%81%D0%B0%D0%BD%D0%B4%D1%80&PRUOT=%D0%9C%D0%B8%D1%85%D0%B0%D0%B9%D0%BB%D0%BE%D0%B2%D0%B8%D1%87&FIRST_NAME=&LAST_NAME=&MIDDLE_NAME=&OVD=&BIRTH_DATE1=&BIRTH_DATE2=&LOST_DATE1=&LOST_DATE2=&SEX=&g-recaptcha-response=03AFcWeA4j72hfpRJmnm_eKEp83-ThEDD5DqfmJatRZgdjZpNSao6aCGFiNbPsALea6tTbxpGtggYoDgHadvgVYaN-Oxrr8EOuVJCL2KKby0l-fcawjzdX38HvqRvYRN93rvaCy-Q_btE8USwNeIRRgrEPEnv59gMilGqaqg8wT5XoQ7R4iB9N3v4oT2KcVtZYLGKWpwYzzIxBvOdAkXz1bgCIhCeCNLDiKVEpity8HGmRKOxaX87CcyOr-zL2yO_N4DK6QXoO5QFn3DhuFsq_7KfI170xdoBDWCWa-_yJ8wHCrlLFTBs4osQUx7_HaiiS6Trx_jnGx-luaogW0KT2_GASxDNRnHxDkS3IMhgthsRmr3CWuUm0SDjuS-TVU_qeDZCM4NNwA5Zaekv0NSUowUza6L-tMSRDz6sZaDblZQOdzpTh-0V9F9lqwUn2qsNM4gLz4Y710iolMR7WD6LYpBwKapVC3oQJAfE24o5Gg-Nm8wmNW3ZHh_vROitYhprVZ9mJl7RD6syjtUfSHIVN5y9QKD8YbCa9V4yVeUXnLVSJTI1hiiFqACOjasFu3VsnobTRSCmd2_8j6gLVH3Y2TGZQezirFlC2aZt4KsAM1VuTZQJKNbU2Q-0
 * @see https://wanted.mvs.gov.ua/searchperson/details/?id=3023314580705560
 */
class UkrWantedPersonSearchProvider extends SearchProvider implements SearchProviderInterface
{
    public const URL = 'https://wanted.mvs.gov.ua';

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
        return SearchProviderName::ukr_wanted_persons;
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

        $persons = $this->searchProviderCompose->tryCatch(fn () => $this->searchPersons($term), null);

        if ($persons === null) {
            return [];
        }

        if (count($persons->getItems()) === 1) {
            sleep(2);
            $url = $persons->getItems()[0]->getHref();

            $person = $this->searchProviderCompose->tryCatch(fn () => $this->searchPerson($url), []);

            return [
                $person,
            ];
        }

        return [
            $persons,
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return true;
    }

    private function searchPersons(string $name): ?UkrWantedPersons
    {
        // todo: add RU names search support

        foreach ($this->ukrPersonNameProvider->getPersonNames($name) as $personName) {
            $query = array_filter([
                'PRUFM' => $personName->getLast(),
                'PRUIM' => $personName->getFirst(),
                'PRUOT' => $personName->getPatronymic(),
            ]);
            $url = '/searchperson?' . http_build_query($query);
            $crawler = $this->crawlerProvider->getCrawler('GET', $url, base: self::URL, user: true);

            $items = $crawler->filter('.cards-list > a.card')->each(static function (Crawler $item): ?UkrWantedPerson {
                $photoEl = $item->filter('img.card-img');

                if ($photoEl->count() > 0) {
                    $photo = trim($photoEl->attr('src') ?? '');
                    $photo = empty($photo) ? null : (self::URL . $photo);
                }

                $href = $item->attr('href') ?? null;
                $href = empty($href) ? null : (self::URL . $href);

                $els = $item->children('.card-info > div');

                if ($els->count() === 0) {
                    return null;
                }

                $regionEl = $els->eq(0);
                $region = trim($regionEl->text());

                $ukrNameEl = $els->eq(2);

                if ($ukrNameEl->count() === 0) {
                    return null;
                }

                $ukrName = trim(str_replace('УКР:', '', $ukrNameEl->text()));
                [$ukrSurname, $ukrName, $ukrPatronymic] = explode(' ', $ukrName);

                if (empty($ukrSurname) || empty($ukrName)) {
                    return null;
                }

                $rusNameEl = $els->eq(1);

                if ($rusNameEl->count() !== 0) {
                    $rusName = trim(str_replace('РОС:', '', $rusNameEl->text()));
                    [$rusSurname, $rusName, $rusPatronymic] = explode(' ', $rusName);
                }

                $bornAtEl = $els->eq(3);

                if ($bornAtEl->count() !== 0) {
                    $bornAt = trim($bornAtEl->text());
                    $bornAt = empty($bornAt) ? null : DateTimeImmutable::createFromFormat('d.m.Y', $bornAt)->setTime(0, 0);
                    $bornAt = $bornAt === false ? null : $bornAt;
                }

                return new UkrWantedPerson(
                    $ukrSurname,
                    $ukrName,
                    ukrPatronymic: empty($ukrPatronymic) ? null : $ukrPatronymic,
                    rusSurname: empty($rusSurname) ? null : $rusSurname,
                    rusName: empty($rusName) ? null : $rusName,
                    rusPatronymic: empty($rusPatronymic) ? null : $rusPatronymic,
                    region: empty($region) ? null : $region,
                    bornAt: empty($bornAt) ? null : $bornAt,
                    photo: empty($photo) ? null : $photo,
                    href: empty($href) ? null : $href
                );
            });

            $items = array_filter($items);

            if (count($items) > 0) {
                return new UkrWantedPersons(array_values($items));
            }
        }

        return null;
    }

    private function searchPerson(string $url): ?UkrWantedPerson
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', $url, user: true);

        $photoEl = $crawler->filter('img.card-img');

        if ($photoEl->count() > 0) {
            $photo = trim($photoEl->attr('src') ?? '');
            $photo = empty($photo) ? null : (self::URL . $photo);
        }

        $els = $crawler->filter('.section-content .info-list > .info-list-item');

        if ($els->count() === 0) {
            return null;
        }

        $map = [
            'Регіон' => 'region',
            'Категорія' => 'category',
            'Дата зникнення' => 'absentAt',
            'Місце зникнення' => 'absentPlace',
            'Прізвище' => 'ukrSurname',
            'І\'мя' => 'ukrName',
            'По батькові' => 'ukrPatronymic',
            'Прізвище російською' => 'rusSurname',
            'Ім`я російською' => 'rusName',
            'По-батькові російською' => 'rusPatronymic',
            'Дата народження' => 'bornAt',
            'Стать' => 'gender',
            'Стаття звинувачення' => 'codexArticle',
            'Запобіжний захід' => 'precaution',
            'Контактна інформація' => 'callTo',
        ];
        $values = array_combine(array_values($map), array_fill(0, count($map), null));

        $els->each(static function (Crawler $el) use ($map, &$values): void {
            $divs = $el->children('div');

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

        $ukrSurname = $values['ukrSurname'];
        $ukrName = $values['ukrName'];

        if (empty($ukrSurname) || empty($ukrName)) {
            return null;
        }

        return new UkrWantedPerson(
            $ukrSurname,
            $ukrName,
            ukrPatronymic: empty($values['ukrPatronymic']) ? null : $values['ukrPatronymic'],
            rusSurname: empty($values['rusSurname']) ? null : $values['rusSurname'],
            rusName: empty($values['rusName']) ? null : $values['rusName'],
            rusPatronymic: empty($values['rusPatronymic']) ? null : $values['rusPatronymic'],
            gender: empty($values['gender']) ? null : $values['gender'],
            region: empty($values['region']) ? null : $values['region'],
            bornAt: empty($values['bornAt']) ? null : (DateTimeImmutable::createFromFormat('d.m.Y', $values['bornAt']) ?: null)?->setTime(0, 0),
            photo: empty($photo) ? null : $photo,
            category: empty($values['category']) ? null : $values['category'],
            absentAt: empty($values['absentAt']) ? null : (DateTimeImmutable::createFromFormat('d.m.Y', $values['absentAt']) ?: null)?->setTime(0, 0),
            absentPlace: empty($values['absentPlace']) ? null : $values['absentPlace'],
            precaution: empty($values['precaution']) ? null : $values['precaution'],
            codexArticle: empty($values['codexArticle']) ? null : $values['codexArticle'],
            callTo: empty($values['callTo']) ? null : $values['callTo'],
        );
    }
}
