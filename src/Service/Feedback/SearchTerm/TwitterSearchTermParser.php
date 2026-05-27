<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class TwitterSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::twitter_username) {
            return true;
        }

        return false;
    }

    private function supportsUsername(string $username): bool
    {
        if (is_numeric($username)) {
            return false;
        }

        return preg_match('/^' . $this->getUsernamePattern(false) . '$/im', $username) === 1;
    }

    private function getUsernamePattern(bool $url): string
    {
        // todo: check
        return ($url ? '' : '@?') . '[A-Za-z0-9-_\.]+';
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?(?:twitter|x)\.com\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $searchTerm
                ->setNormalizedText($this->normalizeUsername($username))
                ->setType(SearchTermType::twitter_username)
            ;
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::twitter_username)
            ;
        }
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::twitter_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            if ($normalizedUsername !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalizedUsername)
                ;
            }
        }
    }
}
