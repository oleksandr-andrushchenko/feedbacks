<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Transfer\Feedback\SearchTermsTransfer;

class MultipleSearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTelegramViewProvider $searchTermTelegramViewProvider,
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly SearchTermProvider $searchTermProvider,
    )
    {
    }

    /**
     * @param SearchTerm[] $feedbackSearchTerms
     */
    public function getFeedbackSearchTermsTelegramView(
        array $feedbackSearchTerms,
        bool $addSecrets = false,
        string $locale = null,
        bool $addTypes = false,
        string $separator = ' ',
    ): string
    {
        $searchTermsItems = array_map(
            fn (SearchTerm $searchTerm) => $this->searchTermProvider->getFeedbackSearchTermTransfer($searchTerm),
            $feedbackSearchTerms
        );
        $searchTerms = new SearchTermsTransfer($searchTermsItems);

        if (!$searchTerms->hasItems()) {
            return '';
        }

        if ($searchTerms->countItems() === 1) {
            return $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                $searchTerms->getFirstItem(),
                $addSecrets,
                locale: $locale
            );
        }

        $views = [];
        foreach ($this->getSortedSearchTerms($searchTerms)->getItemsAsArray() as $searchTerm) {
            $view = $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm, $addSecrets);

            if ($addTypes) {
                $view .= ' [ ';
                $view .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), localeCode: $locale);
                $view .= ' ] ';
            }

            $views[] = $view;
        }

        return implode($separator, $views);
    }

    private function getSortedSearchTerms(SearchTermsTransfer $searchTerms): SearchTermsTransfer
    {
        $sortSearchTermsItems = [];

        $sortTypes = [
            SearchTermType::person_name,
            SearchTermType::organization_name,
            SearchTermType::place_name,
            ...SearchTermType::messengers,
        ];

        foreach ($sortTypes as $type) {
            foreach ($searchTerms->getItems() as $searchTerm) {
                if ($searchTerm->getType() === $type) {
                    $sortSearchTermsItems[] = $searchTerm;
                }
            }
        }

        foreach ($searchTerms->getItems() as $searchTerm) {
            if (!in_array($searchTerm, $sortSearchTermsItems, true)) {
                $sortSearchTermsItems[] = $searchTerm;
            }
        }

        return new SearchTermsTransfer($sortSearchTermsItems);
    }
}