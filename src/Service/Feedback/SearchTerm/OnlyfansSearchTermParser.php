<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class OnlyfansSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::onlyfans_username) {
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
        return ($url ? '' : '@?') . '\w(?!.*?\.{2})[\w.-]{1,28}\w';
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?onlyfans\.com\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

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
                ->setType(SearchTermType::onlyfans_username)
            ;
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::onlyfans_username)
            ;
        }
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::onlyfans_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            if ($normalizedUsername !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalizedUsername)
                ;
            }
        }
    }
}
