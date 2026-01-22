<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\UkrCorrupt\UkrCorruptPerson;
use App\Model\Search\UkrCorrupt\UkrCorruptPersons;
use App\Service\HttpRequester;
use App\Service\Intl\Ukr\UkrPersonNameProvider;
use DateTimeImmutable;

/**
 * @see https://corruptinfo.nazk.gov.ua/
 * @see https://corruptinfo.nazk.gov.ua/ep/swagger/ui/index
 */
class UkrCorruptSearchProvider extends SearchProvider implements SearchProviderInterface
{
    private const URL = 'https://corruptinfo.nazk.gov.ua/ep/1.0/corrupt/findData';

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
        return SearchProviderName::ukr_corrupts;
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
            $this->searchPersons($term),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return true;
    }

    private function searchPersons(string $name): ?UkrCorruptPersons
    {
        foreach ($this->ukrPersonNameProvider->getPersonNames($name) as $personName) {
            $body = array_filter([
                'indLastNameOnOffenseMoment' => $personName->getLast(),
                'indFirstNameOnOffenseMoment' => $personName->getFirst(),
                'indPatronymicOnOffenseMoment' => $personName->getPatronymic(),
            ]);
            $rows = $this->httpRequester->requestHttp('POST', self::URL, json: $body, array: true);

            $items = [];

            foreach ($rows as $row) {
                $items[] = new UkrCorruptPerson(
                    punishmentType: isset($row['punishmentType'], $row['punishmentType']['name']) ? $row['punishmentType']['name'] : null,
                    entityType: isset($row['entityType'], $row['entityType']['name']) ? $row['entityType']['name'] : null,
                    lastName: $row['indLastNameOnOffenseMoment'] ?? null,
                    firstName: $row['indFirstNameOnOffenseMoment'] ?? null,
                    patronymic: $row['indPatronymicOnOffenseMoment'] ?? null,
                    offenseName: $row['offenseName'] ?? null,
                    punishment: $row['punishment'] ?? null,
                    courtCaseNumber: $row['courtCaseNumber'] ?? null,
                    sentenceDate: isset($row['sentenceDate']) ? DateTimeImmutable::createFromFormat('Y-m-d', $row['sentenceDate'])->setTime(0, 0) : null,
                    punishmentStart: isset($row['punishmentStart']) ? DateTimeImmutable::createFromFormat('Y-m-d', $row['punishmentStart'])->setTime(0, 0) : null,
                    courtName: $row['courtName'] ?? null,
                    codexArticles: isset($row['codexArticles']) ? array_map(fn (array $article) => $article['codexArticleName'], $row['codexArticles']) : null
                );
            }

            $items = array_filter($items, static function (UkrCorruptPerson $item) use ($personName): bool {
                if (!empty($personName->getFirst()) && !str_contains($item->getFirstName(), $personName->getFirst())) {
                    return false;
                }

                if (!empty($personName->getLast()) && !str_contains($item->getLastName(), $personName->getLast())) {
                    return false;
                }

                if (!empty($personName->getPatronymic()) && !str_contains($item->getPatronymic(), $personName->getPatronymic())) {
                    return false;
                }

                return true;
            });

            if (count($items) > 0) {
                return new UkrCorruptPersons(array_values($items));
            }
        }

        return null;
    }
}
