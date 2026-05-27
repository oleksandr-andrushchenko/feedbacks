<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermParser implements SearchTermParserInterface
{
    public function __construct(
        private readonly SearchTermParserRegistry $searchTermParserRegistry,
    )
    {
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() !== null) {
            return;
        }

        foreach ($this->searchTermParserRegistry->getSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm, $context)) {
                $searchTermParser->parseWithGuessType($searchTerm, $context);
            }
        }
    }

    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        foreach ($this->searchTermParserRegistry->getSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm, $context)) {
                return true;
            }
        }

        return false;
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === null) {
            return;
        }

        foreach ($this->searchTermParserRegistry->getSearchTermParsers() as $searchTermParser) {
            if ($searchTermParser->supportsSearchTerm($searchTerm, $context)) {
                $searchTermParser->parseWithKnownType($searchTerm, $context);
            }
        }
    }
}
