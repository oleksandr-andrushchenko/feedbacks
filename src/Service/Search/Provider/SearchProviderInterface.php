<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Search\SearchProviderName;

interface SearchProviderInterface
{
    public function getName(): SearchProviderName;

    public function supports(SearchTerm $searchTerm, array $context = []): bool;

    public function search(SearchTerm $searchTerm, array $context = []): array;

    public function goodOnEmptyResult(): ?bool;
}
