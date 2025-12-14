<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\CleanTalk\CleanTalkEmail;
use App\Model\Search\CleanTalk\CleanTalkEmails;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://cleantalk.org/email-checker/lisiy17@ukr.net
 * @see https://cleantalk.org/email-checker/alex.snowgirl@gmail.com
 * @see https://cleantalk.org/price-database-api
 */
class CleanTalkSearchProvider extends SearchProvider implements SearchProviderInterface
{
    private const URL = 'https://cleantalk.org';

    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly CrawlerProvider $crawlerProvider,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::clean_talk;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $type = $searchTerm->getType();

        if ($type === SearchTermType::email) {
            return true;
        }

        return false;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchEmails($term),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }

    private function searchEmails(string $email): ?CleanTalkEmails
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', '/email-checker/' . $email, base: self::URL, user: true);

        $table = $crawler->filter('#result-table');

        if ($table->count() === 0) {
            return null;
        }

        $header = [];
        $table->filter('thead th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        if (!isset($header[0]) || !str_contains($header[0], 'Record')) {
            return null;
        }

        if (!isset($header[1]) || !str_contains($header[1], 'sites')) {
            return null;
        }

        if (!isset($header[2]) || !str_contains($header[2], 'Blacklisted')) {
            return null;
        }

        if (!isset($header[3]) || !str_contains($header[3], 'Real')) {
            return null;
        }

        if (!isset($header[4]) || !str_contains($header[4], 'Disposable')) {
            return null;
        }

        if (!isset($header[5]) || !str_contains($header[5], 'update')) {
            return null;
        }

        $items = $table->filter('tbody tr')->each(static function (Crawler $tr): ?CleanTalkEmail {
            $tds = $tr->filter('td');

            if ($tds->count() === 0) {
                return null;
            }

            $addressEl = $tds->filter('a');

            if ($addressEl->count() === 0) {
                return null;
            }

            $href = $addressEl->attr('href');

            if (empty($href)) {
                return null;
            }

            $href = self::URL . $href;

            $address = trim($addressEl->text());

            if (empty($address)) {
                return null;
            }

            $attackedSitesEl = $tds->eq(1);

            if ($attackedSitesEl->count() === 0) {
                return null;
            }

            $attackedSites = trim($attackedSitesEl->text());
            $attackedSites = $attackedSites === '-' ? 0 : (int) $attackedSites;

            $blacklistedEl = $tds->eq(2);

            if ($blacklistedEl->count() === 0) {
                return null;
            }

            $blacklisted = trim($blacklistedEl->text());
            $blacklisted = !in_array($blacklisted, ['-', 'Not in list'], true);

            $realEl = $tds->eq(3);

            if ($realEl->count() === 0) {
                return null;
            }

            $real = trim($realEl->text());
            $real = in_array($real, ['Real'], true);

            $disposableEl = $tds->eq(4);

            if ($disposableEl->count() === 0) {
                return null;
            }

            $disposable = trim($disposableEl->text());
            $disposable = !in_array($disposable, ['-', 'No'], true);

            $lastUpdateEl = $tds->eq(5);

            if ($lastUpdateEl->count() === 0) {
                return null;
            }

//            $lastUpdate = trim($lastUpdateEl->text());
//            $lastUpdate = empty($lastUpdate) ? null : DateTimeImmutable::createFromFormat('M d, Y H:i:s', $lastUpdate)->setTime(0, 0);
//            $lastUpdate = $lastUpdate === false ? null : $lastUpdate;
            $lastUpdate = (new DateTimeImmutable())->setTime(0, 0);

            return new CleanTalkEmail(
                $address,
                $href,
                $attackedSites,
                $blacklisted,
                $real,
                $disposable,
                empty($lastUpdate) ? null : $lastUpdate
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new CleanTalkEmails(array_values($items));
        }

        return null;
    }
}
