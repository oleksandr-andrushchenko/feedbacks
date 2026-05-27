<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class CrawlerProvider
{
    public function __construct(
        private readonly HttpRequester $httpRequester,
    )
    {
    }

    public function getCrawler(
        string $method,
        string $url,
        string $base = null,
        array $headers = null,
        array $body = null,
        array $json = null,
        callable $contentModifier = null,
        float $timeout = 15.0,
        bool $user = false
    ): Crawler
    {
        static $crawlers = [];

        if ($base !== null) {
            $url = $base . $url;
        }

        if (!isset($crawlers[$url])) {
            $content = $this->httpRequester->requestHttp(
                $method,
                $url,
                headers: $headers,
                body: $body,
                json: $json,
                timeout: $timeout,
                user: $user
            );

            if ($contentModifier !== null) {
                $content = $contentModifier($content);
            }

            $crawlers[$url] = new Crawler($content, baseHref: $base);
        }

        return $crawlers[$url];
    }
}