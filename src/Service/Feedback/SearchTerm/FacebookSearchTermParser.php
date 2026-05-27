<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;

class FacebookSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::facebook_username) {
            return true;
        }

        return false;
    }

    private function supportsUsername(string $username): bool
    {
        return preg_match('/^' . $this->getUsernamePattern(false) . '$/im', $username) === 1;
    }

    private function getUsernamePattern(bool $url): string
    {
        return ($url ? '' : '@?') . '[a-zA-Z0-9-_\.]+';
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?(?:m\.)?facebook\.com\/profile\.php\?id=([0-9]+)/', $url, $matches);

        if ($result === 1) {
            $username = $matches[1];

            return true;
        }

        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?(?:m\.)?facebook\.com\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $this->setupSearchTerm($searchTerm, $username);
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::facebook_username)
            ;
        }
    }

    public function setupSearchTerm(SearchTermTransfer $searchTerm, string $username): void
    {
        $searchTerm
            ->setType(SearchTermType::facebook_username)
        ;

        $normalizedUsername = $this->normalizeUsername($username);

        if ($searchTerm->getText() !== $normalizedUsername) {
            $searchTerm
                ->setNormalizedText($normalizedUsername)
            ;
        }

        if (is_numeric($username)) {
            $searchTerm
                ->setMessengerUser(new MessengerUserTransfer(Messenger::facebook, $username))
            ;
        }
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::facebook_username) {
            $this->setupSearchTerm($searchTerm, $searchTerm->getText());
        }
    }
}
