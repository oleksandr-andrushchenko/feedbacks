<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\SearchTerm;

interface SearchViewerInterface
{
    public function getOnSearchMessage(SearchTerm $searchTerm, array $context = []): string;

    public function showLimits(): bool;

    public function getLimitsMessage(): string;

    public function getEmptyMessage(SearchTerm $searchTerm, array $context = [], bool $good = null): string;

    public function getErrorMessage(SearchTerm $searchTerm, array $context = []): string;

    public function getResultMessage($record, SearchTerm $searchTerm, array $context = []): string;
}
