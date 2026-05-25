<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class TaxNumberSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supports($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::tax_number) {
            return true;
        }

        return false;
    }

    private function supports(string $number): bool
    {
        return preg_match("/^[0-9][0-9\-]{7,}$/", $number) === 1;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($this->supports($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::tax_number)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::tax_number) {
            $normalized = $this->normalize($searchTerm->getText());

            if ($normalized !== $searchTerm->getText()) {
                $searchTerm
                    ->setNormalizedText($normalized)
                ;
            }
        }
    }

    private function normalize(string $number): ?string
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}