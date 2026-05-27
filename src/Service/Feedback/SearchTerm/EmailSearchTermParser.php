<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class EmailSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return filter_var($this->normalizeEmail($searchTerm->getText()), FILTER_VALIDATE_EMAIL) !== false;
        }

        if ($searchTerm->getType() === SearchTermType::email) {
            return true;
        }

        return false;
    }

    private function normalizeEmail(string $email)
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        $normalized = $this->normalizeEmail($searchTerm->getText());

        if ($normalized === $searchTerm->getText()) {
            $searchTerm
                ->setType(SearchTermType::email)
            ;
        } else {
            $searchTerm
                ->addType(SearchTermType::email)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::email) {
            $normalized = $this->normalizeEmail($searchTerm->getText());

            if ($normalized !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalized)
                ;
            }
        }
    }
}