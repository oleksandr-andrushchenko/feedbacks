<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReview;
use App\Model\Search\ShouldIAnswer\ShouldIAnswerReviews;
use App\Service\CrawlerProvider;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://www.shouldianswer.com/phone-number/5613144508
 * @see https://www.shouldianswer.com/phone-number/5613141610
 * @see https://www.shouldianswer.com/phone-number/5613146359
 */
class ShouldIAnswerSearchProvider extends SearchProvider implements SearchProviderInterface
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
        return SearchProviderName::should_i_answer;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'us') {
            return false;
        }

        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type === SearchTermType::phone_number) {
            if (!str_starts_with($term, '1')) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();

        return [
            $this->searchPhones($term),
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }

    private function searchPhones(string $phone): ?ShouldIAnswerReviews
    {
        if (str_starts_with($phone, '1')) {
            $phone = substr($phone, 1);
        }

        $crawler = $this->crawlerProvider->getCrawler('GET', 'https://www.shouldianswer.com/phone-number/' . $phone, user: true);

        $scoreEl = $crawler->filter('.mainInfo .mainInfoHeader .score');

        if ($scoreEl->count() > 0) {
            $score = trim($scoreEl->attr('class') ?? '');
            $score = str_contains($score, 'negative') ? -1 : (str_contains($score, 'positive') ? 1 : 0);
        }

        $headerEl = $crawler->filter('.mainInfo .mainInfoHeader .number');

        if ($headerEl->count() === 0) {
            return null;
        }

        $header = trim($headerEl->text());

        $infoEl = $crawler->filter('.mainInfo .infox');

        if ($infoEl->count() === 0) {
            return null;
        }

        $info = trim($infoEl->text());

        $items = $crawler->filter('.containerReviews .review')->each(static function (Crawler $item): ?ShouldIAnswerReview {
            $nameEl = $item->filter('[itemprop="name"]');

            if ($nameEl->count() === 0) {
                return null;
            }

            $name = trim($nameEl->text());

            $authorEl = $item->filter('[itemprop="author"]');

            if ($authorEl->count() === 0) {
                return null;
            }

            $author = trim($authorEl->text());

            $ratingEl = $item->filter('[itemprop="ratingValue"]');

            if ($ratingEl->count() === 0) {
                return null;
            }

            $rating = (int) ($ratingEl->attr('content') ?? 0);

            $datePublishedEl = $item->filter('[itemprop="datePublished"]');

            if ($datePublishedEl->count() === 0) {
                return null;
            }

            $datePublished = trim($datePublishedEl->attr('datetime') ?? '');
            $datePublished = empty($datePublished) ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datePublished);
            $datePublished = $datePublished === false ? null : $datePublished;

            $descriptionEl = $item->filter('[itemprop="description"]');

            if ($descriptionEl->count() > 0) {
                $description = trim($descriptionEl->text());
            }

            return new ShouldIAnswerReview(
                $name,
                $author,
                $rating,
                $datePublished,
                description: empty($description) ? null : $description
            );
        });

        $items = array_filter($items);

        return new ShouldIAnswerReviews(
            $header,
            $info,
            score: $score ?? null,
            items: array_values($items)
        );
    }
}
