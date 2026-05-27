<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;

interface SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool;

    /**
     * Set search term type if 100% match, otherwise - set possible types
     *
     * @param SearchTermTransfer $searchTerm
     * @param array $context
     * @return void
     */
    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void;

    /**
     * Set search term info according to known type, from search term itself
     *
     * @param SearchTermTransfer $searchTerm
     * @param array $context
     * @return void
     */
    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void;
}
