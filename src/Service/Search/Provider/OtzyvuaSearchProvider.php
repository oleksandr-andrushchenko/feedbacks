<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\Otzyvua\OtzyvuaFeedback;
use App\Model\Search\Otzyvua\OtzyvuaFeedbacks;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerm;
use App\Model\Search\Otzyvua\OtzyvuaFeedbackSearchTerms;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://www.otzyvua.net/uk/privat-bank.html
 * @see https://www.otzyvua.net/ajax/?mode=search&q=%D0%BF%D1%80%D0%B8%D0%B2%D0%B0%D1%82
 * @see https://www.otzyvua.net/search/?q=380965137629
 */
class OtzyvuaSearchProvider extends SearchProvider implements SearchProviderInterface
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
        return SearchProviderName::otzyvua;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();

        if (in_array($type, [
            SearchTermType::email,
            SearchTermType::phone_number,
            SearchTermType::organization_name,
            SearchTermType::place_name,
        ], true)) {
            return true;
        }

        return false;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();
        $searchTerms = $this->searchFeedbackSearchTerms($term);

        if ($searchTerms === null) {
            return [];
        }

        if (count($searchTerms->getItems()) === 1) {
            $url = $searchTerms->getItems()[0]->getHref();
        } else {
            $equals = array_filter(
                $searchTerms->getItems(),
                static fn (OtzyvuaFeedbackSearchTerm $searchTerm): bool => strcmp(mb_strtolower($term), mb_strtolower($searchTerm->getName())) === 0
            );

            if (!empty($equals)) {
                /** @var OtzyvuaFeedbackSearchTerm $searchTerm */
                $searchTerm = array_shift($equals);
                $url = $searchTerm->getHref();
            }
        }

        if (isset($url)) {
            sleep(1);
            $feedbacks = $this->searchProviderCompose->tryCatch(fn () => $this->searchFeedbacks($url), null);

            return [
                $feedbacks,
            ];
        }

        return [
            $searchTerms,
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }

    private function searchFeedbackSearchTerms(string $name): ?OtzyvuaFeedbackSearchTerms
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', 'https://www.otzyvua.net/uk/search/?q=' . urlencode($name), user: true);

        $items = $crawler->filter('#container .otzyv_box_float .row')->each(static function (Crawler $item): ?OtzyvuaFeedbackSearchTerm {
            $col = $item->filter('.col')->last();
            $nameEl = $col->filter('h2 a');

            if ($nameEl->count() === 0) {
                return null;
            }

            $name = trim($nameEl->eq(0)->text());
            $href = trim($nameEl->eq(0)->attr('href') ?? '');

            if (empty($name)) {
                return null;
            }

            $categoryEl = $col->filter('.h2_descr');

            if ($categoryEl->count() !== 0) {
                $category = trim($categoryEl->eq(0)->text());
            }

            $ratingEl = $col->filter('.rtng_val');

            if ($ratingEl->count() !== 0) {
                $rating = trim($ratingEl->eq(0)->text());
                $rating = empty($rating) ? null : (float) $rating;
            }

            $countEl = $col->filter('.num_rev');

            if ($countEl->count() !== 0) {
                $count = trim(preg_replace('/[^0-9]/', '', $countEl->eq(0)->text()) ?? '');
                $count = empty($count) ? null : (int) $count;
            }

            return new OtzyvuaFeedbackSearchTerm(
                $name,
                $href,
                category: empty($category) ? null : $category,
                rating: empty($rating) ? null : $rating,
                count: $count ?? null
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new OtzyvuaFeedbackSearchTerms(array_values($items));
        }

        return null;
    }

    private function searchFeedbacks(string $url): ?OtzyvuaFeedbacks
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', $url);

        $items = $crawler->filter('#comments_container .commentbox')->each(static function (Crawler $item): ?OtzyvuaFeedback {
            $row = $item->filter('.comment_row')->first();
            $titleEl = $row->filter('h2 a');

            if ($titleEl->count() < 1) {
                return null;
            }

            $title = trim($titleEl->eq(0)->text());
            $href = trim($titleEl->eq(0)->attr('href') ?? '');

            if (empty($title)) {
                return null;
            }

            $ratingEl = $row->filter('.star_ring span');

            if ($ratingEl->count() !== 0) {
                $width = preg_replace('/[^0-9]/', '', trim($ratingEl->eq(0)->attr('style') ?? ''));
                $width = empty($width) ? null : (int) $width;
                $rating = empty($width) ? null : (int) ($width / 13);
            }

            $authorEl = $row->filter('.author_name ins');

            if ($authorEl->count() !== 0) {
                $authorName = trim($authorEl->eq(0)->text());

                if ($authorEl->filter('a')->count() !== 0) {
                    $authorHref = trim($authorEl->filter('a')->eq(0)->attr('href') ?? '');
                }
            }

            $createdEl = $row->filter('.dtreviewed .value-title');

            if ($createdEl->count() !== 0) {
                $createdAt = trim($createdEl->eq(0)->attr('title') ?? '');
                $createdAt = empty($createdAt) ? null : DateTimeImmutable::createFromFormat('Y-m-d', $createdAt)->setTime(0, 0);
                $createdAt = $createdAt === false ? null : $createdAt;
            }

            $descEl = $row->filter('.comment.description');

            if ($descEl->count() !== 0) {
                if ($descEl->filter('.review-full-text')->count() !== 0) {
                    $description = trim($descEl->filter('.review-full-text')->text());
                } elseif ($descEl->filter('.review-snippet')->count() !== 0) {
                    $description = trim($descEl->filter('.review-snippet')->text());
                }
            }

            return new OtzyvuaFeedback(
                $title,
                $href,
                rating: empty($rating) ? null : $rating,
                authorName: empty($authorName) ? null : $authorName,
                authorHref: empty($authorHref) ? null : $authorHref,
                description: empty($description) ? null : $description,
                createdAt: empty($createdAt) ? null : $createdAt,
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new OtzyvuaFeedbacks(array_values($items));
        }

        return null;
    }
}
