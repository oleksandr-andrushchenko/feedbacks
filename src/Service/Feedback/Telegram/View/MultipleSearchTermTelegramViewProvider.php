<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Service\Feedback\SearchTerm\SearchTermProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Transfer\Feedback\SearchTermsTransfer;
use App\Transfer\Feedback\SearchTermTransfer;

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
     * @param bool $addSecrets
     * @param string|null $locale
     * @return string
     */
    public function getFeedbackSearchTermsTelegramView(
        array $feedbackSearchTerms,
        bool $addSecrets = false,
        string $locale = null,
    ): string
    {
        $searchTermsItems = array_map(
            fn (SearchTerm $searchTerm): SearchTermTransfer => $this->searchTermProvider->getFeedbackSearchTermTransfer($searchTerm),
            $feedbackSearchTerms
        );
        $searchTerms = new SearchTermsTransfer($searchTermsItems);

        if (!$searchTerms->hasItems()) {
            return '';
        }

        if ($searchTerms->countItems() === 1) {
            return $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
                $searchTerms->getFirstItem(),
                addSecrets: $addSecrets,
                localeCode: $locale
            );
        }

        $sortedSearchTerms = $this->getSortedSearchTerms($searchTerms);
        $searchTerm = $sortedSearchTerms->shiftFirstItem();

        $message = $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);
        $message .= ' [ ';

        $message .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), localeCode: $locale);

        foreach ($sortedSearchTerms as $searchTerm) {
            $message .= ', ';
            $message .= $this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), localeCode: $locale);
            $message .= ': ';
            $message .= $this->searchTermTelegramViewProvider->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets);
        }

        $message .= ' ] ';

        return $message;
    }

    public function getPrimarySearchTermTelegramView(
        SearchTermsTransfer $searchTerms,
        bool $addSecrets = false,
        bool $forceType = true,
        string $locale = null
    ): string
    {
        if (!$searchTerms->hasItems()) {
            return '';
        }

        $searchTerm = $this->getSortedSearchTerms($searchTerms)->getFirstItem();

        return $this->searchTermTelegramViewProvider->getSearchTermTelegramView(
            $searchTerm,
            addSecrets: $addSecrets,
            forceType: $forceType,
            localeCode: $locale
        );
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