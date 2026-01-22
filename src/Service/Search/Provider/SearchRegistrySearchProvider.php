<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Search\SearchProviderName;
use App\Service\Feedback\FeedbackSearchSearcher;

class SearchRegistrySearchProvider extends SearchProvider implements SearchProviderInterface
{
    public function __construct(
        SearchProviderCompose $searchProviderCompose,
        private readonly FeedbackSearchSearcher $feedbackSearchSearcher,
    )
    {
        parent::__construct($searchProviderCompose);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::searches;
    }

    public function supports(SearchTerm $searchTerm, array $context = []): bool
    {
        return true;
    }

    public function search(SearchTerm $searchTerm, array $context = []): array
    {
        $items = $this->feedbackSearchSearcher->searchFeedbackSearches($searchTerm, withUsers: $context['addTime'] ?? false);

        $skipFirstItem = $context['skipFirstItem'] ?? false;

        if ($skipFirstItem) {
//            usort($items, static fn (FeedbackSearch $fs1, FeedbackSearch $fs2): int => $fs1->getCreatedAt() <=> $fs2->getCreatedAt());
            array_pop($items);
        }

        return [
            $items,
        ];
    }

    public function goodOnEmptyResult(): ?bool
    {
        return null;
    }
}
