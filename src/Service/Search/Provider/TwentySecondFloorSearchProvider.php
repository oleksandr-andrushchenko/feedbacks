<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorBlogger;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorBloggers;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorFeedback;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorFeedbacks;
use App\Service\CrawlerProvider;
use App\Service\Intl\TimeProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @see https://22flr.com/search/?q=gorovaya
 * @see https://22flr.com/account/anya.rapatska/reviews/
 */
class TwentySecondFloorSearchProvider extends SearchProvider implements SearchProviderInterface
{
    private const URL = 'https://22flr.com';

    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly CrawlerProvider $crawlerProvider,
        private readonly TimeProvider $timeProvider,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::twenty_second_floor;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();

        if (in_array($type, [
            SearchTermType::instagram_username,
            SearchTermType::telegram_username,
            SearchTermType::facebook_username,
            SearchTermType::tiktok_username,
            SearchTermType::youtube_username,
            SearchTermType::messenger_username,
        ], true)) {
            return true;
        }

        return false;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $term = $searchTerm->getNormalizedText();
        $bloggers = $this->searchBloggers($term);

        if ($bloggers === null) {
            return [];
        }

        if (count($bloggers->getItems()) === 1) {
            $url = $bloggers->getItems()[0]->getHref();
        } else {
            $equals = array_filter(
                $bloggers->getItems(),
                static fn (TwentySecondFloorBlogger $blogger): bool => strcmp(mb_strtolower($term), mb_strtolower($blogger->getName())) === 0
            );

            if (!empty($equals)) {
                /** @var TwentySecondFloorBlogger $blogger */
                $blogger = array_shift($equals);
                $url = $blogger->getHref();
            }
        }

        if (isset($url)) {
            sleep(1);
            $feedbacks = $this->searchProviderCompose->tryCatch(fn () => $this->searchFeedbacks($url . 'reviews/'), null);

            return [
                $feedbacks,
            ];
        }

        return [
            $bloggers,
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }

    private function searchBloggers(string $name): ?TwentySecondFloorBloggers
    {
        $headers = [
            'Referer' => self::URL,
        ];
        $crawler = $this->crawlerProvider->getCrawler('GET', '/search/?q=' . urlencode($name), base: self::URL, headers: $headers, user: true);

        $table = $crawler->filter('#accountstable');

        if ($table->count() === 0) {
            return null;
        }

        $header = [];
        $table->filter('thead th')->each(function (Crawler $th) use (&$header) {
            $header[] = trim($th->text());
        });

        $items = $table->filter('tbody tr')->each(static function (Crawler $tr) use ($header): ?TwentySecondFloorBlogger {
            $tds = $tr->filter('td');

            if ($tds->count() === 0) {
                return null;
            }

            $nameLinkEl = $tds->filter('a');

            if ($nameLinkEl->count() === 0) {
                return null;
            }

            $href = trim($nameLinkEl->eq(0)->attr('href') ?? '');
            $href = self::URL . $href;

            $photoImgEl = $nameLinkEl->filter('img');

            if ($photoImgEl->count() > 0) {
                $photo = trim($photoImgEl->eq(0)->attr('src') ?? '');
                $photo = $photo === 'None' ? null : $photo;
            }

            $nameEl = $nameLinkEl->filter('.table-username');

            if ($nameEl->count() > 0) {
                $name = trim($nameEl->text());
            }

            if (empty($name)) {
                return null;
            }

            $descEl = $nameLinkEl->filter('.table-desc');

            if ($descEl->count() > 0) {
                $desc = trim($descEl->text());
            }

            if (isset($header[1]) && str_contains($header[1], 'Підписників')) {
                $followersEl = $tds->eq(1);

                if ($followersEl->count() > 0) {
                    $followers = trim(preg_replace('/[^0-9]/', '', $followersEl->eq(0)->text()) ?? '');
                    $followers = empty($followers) ? null : (int) $followers;
                }
            }

            return new TwentySecondFloorBlogger(
                $name,
                $href,
                photo: empty($photo) ? null : $photo,
                desc: empty($desc) ? null : $desc,
                followers: $followers ?? null
            );
        });

        $items = array_filter($items);

        if (count($items) > 0) {
            return new TwentySecondFloorBloggers(array_values($items));
        }

        return null;
    }

    private function searchFeedbacks(string $url): ?TwentySecondFloorFeedbacks
    {
        $crawler = $this->crawlerProvider->getCrawler('GET', $url);

        $items = $crawler->filter('.ui.comments .comment > .content')->each(function (Crawler $item): ?TwentySecondFloorFeedback {
            $els = $item->children();

            if ($els->count() === 0) {
                return null;
            }

            $textEl = $els->filter('.text');

            if ($textEl->count() === 0) {
                return null;
            }

            $ratingEl = $textEl->filter('.label');

            if ($ratingEl->count() > 0) {
                $ratingClass = $ratingEl->attr('class') ?? '';

                if (str_contains($ratingClass, 'green')) {
                    $mark = 1;
                } elseif (str_contains($ratingClass, 'red')) {
                    $mark = -1;
                } else {
                    $mark = 0;
                }

                $ratingElNode = $ratingEl->getNode(0);
                $ratingElNode->parentNode->removeChild($ratingElNode);
            }

            $text = trim($textEl->text());

            $authorEl = $els->eq(0);

            if ($authorEl->count() > 0) {
                $author = trim($authorEl->text());
            }

            $dateEl = $els->filter('.date');

            if ($dateEl->count() > 0) {
                $date = trim($dateEl->text());
                $date = empty($date) ? null : $this->timeProvider->createFromMonthYear($date, 'uk');
            }

            return new TwentySecondFloorFeedback(
                $text,
                header: null,
                mark: $mark ?? null,
                author: empty($author) ? null : $author,
                date: empty($date) ? null : $date
            );
        });

        $blackListEl = $crawler->filter('div.red.message');

        if ($blackListEl->count() > 0) {
            $contentEl = $blackListEl->filter('.content');

            if ($contentEl->count() > 0) {
                $headerEl = $contentEl->filter('.header');

                if ($headerEl->count() > 0) {
                    $header = trim($headerEl->text());
                }

                $textEl = $contentEl->filter('p');

                if ($textEl->count() > 0) {
                    $text = trim($textEl->text());
                }

                if (!empty($text)) {
                    array_unshift($items, new TwentySecondFloorFeedback(
                        $text,
                        header: empty($header) ? null : $header,
                        mark: -1
                    ));
                }
            }
        }

        $items = array_filter($items);

        if (count($items) > 0) {
            return new TwentySecondFloorFeedbacks(array_values($items));
        }

        return null;
    }
}
