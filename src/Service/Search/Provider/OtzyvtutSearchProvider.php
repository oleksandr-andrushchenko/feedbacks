<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\Otzyvtut\OtzyvtutFeedback;
use App\Model\Search\Otzyvtut\OtzyvtutFeedbacks;
use App\Model\Search\Otzyvtut\OtzyvtutFeedbackSearchTerm;
use App\Model\Search\Otzyvtut\OtzyvtutFeedbackSearchTerms;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://otzyvtut.com/?s=%D1%80%D0%BE%D0%BC%D0%B0%D0%BD%D1%86%D0%BE%D0%B2
 * @see https://otzyvtut.com/flp-romancev-r-i/
 */
class OtzyvtutSearchProvider extends SearchProvider implements SearchProviderInterface
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
        return SearchProviderName::otzyvtut;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if (!in_array($countryCode, ['ua', 'ru', 'kz', 'by'], true)) {
            return false;
        }

        $type = $searchTerm->getType();

        if (in_array($type, [
            SearchTermType::email,
            SearchTermType::phone_number,
            SearchTermType::person_name,
            SearchTermType::organization_name,
            SearchTermType::place_name,
            SearchTermType::url,
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
                static fn (OtzyvtutFeedbackSearchTerm $searchTerm): bool => strcmp(mb_strtolower($term), mb_strtolower($searchTerm->getName())) === 0
            );

            if (!empty($equals)) {
                /** @var OtzyvtutFeedbackSearchTerm $searchTerm */
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

    private function searchFeedbackSearchTerms(string $name): ?OtzyvtutFeedbackSearchTerms
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', 'https://otzyvtut.com/?s=' . urlencode($name), user: true);

        $items = $crawler->filter('.content-masonry .post-masonry')->each(static function (Crawler $item): ?OtzyvtutFeedbackSearchTerm {
            $hrefEl = $item->filter('.link-to-company');

            if ($hrefEl->count() === 0) {
                return null;
            }

            $href = $hrefEl->attr('href');

            $postHeaderEl = $item->filter('.post-header');
            $photoEl = $postHeaderEl->filter('img');

            if ($photoEl->count() > 0) {
                $photo = $photoEl->attr('src') ?? null;
            }

            $reviewsEl = $postHeaderEl->filter('.reviews');

            if ($reviewsEl->count() > 0) {
                $count = explode(' ', trim($reviewsEl->text()));
                $count = count($count) > 0 && is_numeric($count[0]) ? (int) $count : null;
            }

            $titlesEl = $postHeaderEl->filter('.titles');

            if ($titlesEl->count() === 0) {
                return null;
            }

            $nameEl = $titlesEl->filter('.name');

            if ($nameEl->count() === 0) {
                return null;
            }

            $companyCategoryEl = $nameEl->filter('.company-category');

            if ($companyCategoryEl->count() > 0) {
                $category = trim($companyCategoryEl->text());

                $toRemove = $companyCategoryEl->getNode(0);
                $toRemove->parentNode->removeChild($toRemove);
            }

            $name = trim($nameEl->text());

            $ratingEl = $titlesEl->filter('.rating-wrap .number');

            if ($ratingEl->count() > 0) {
                $rating = trim($ratingEl->text());
                $rating = empty($rating) ? null : (float) $rating;
            }

            $postTextEl = $item->filter('.post-text .desc');

            if ($postTextEl->count() > 0) {
                $desc = trim($postTextEl->text());
            }

            return new OtzyvtutFeedbackSearchTerm(
                $name,
                $href,
                photo: empty($photo) ? null : $photo,
                category: empty($category) ? null : $category,
                rating: empty($rating) ? null : $rating,
                desc: empty($desc) ? null : $desc,
                count: $count ?? null
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new OtzyvtutFeedbackSearchTerms(array_values($items));
        }

        return null;
    }

    private function searchFeedbacks(string $url): ?OtzyvtutFeedbacks
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', $url);

        $items = $crawler->filter('.comments-wrap .comment')->each(static function (Crawler $item): ?OtzyvtutFeedback {
            $json = $item->attr('data-review-json');

            if (empty($json)) {
                return null;
            }

            $data = json_decode($json, associative: true);

            if (empty($data)) {
                return null;
            }

            $title = $data['review_title'] ?? null;

            if (empty($title)) {
                return null;
            }

            $rate = isset($data['review_rate']) ? (int) $data['review_rate'] : null;

            if ($rate === null) {
                return null;
            }

            return new OtzyvtutFeedback(
                title: $title,
                rate: $rate,
                text: $data['review_text'] ?? null,
                pluses: $data['review_pluses'] ?? null,
                minuses: $data['review_minuses'] ?? null,
                companyTitle: $data['review_company_title'] ?? null,
                author: $data['author_name'] ?? null,
                dateAdded: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['date_added'])
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new OtzyvtutFeedbacks(array_values($items));
        }

        return null;
    }
}
