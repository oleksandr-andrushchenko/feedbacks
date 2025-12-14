<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedbackSearch;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Repository\Feedback\SearchTermFeedbackSearchDynamodbRepository;
use App\Repository\User\UserRepository;

class FeedbackSearchSearcher
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly SearchTermFeedbackSearchDynamodbRepository $searchTermFeedbackSearchDynamodbRepository,
        private readonly UserRepository $userRepository,
    )
    {
    }

    /**
     * @param SearchTerm $searchTerm
     * @param bool $withUsers
     * @param int $maxResults
     * @return array<FeedbackSearch>
     */
    public function searchFeedbackSearches(SearchTerm $searchTerm, bool $withUsers = false, int $maxResults = 20): array
    {
        $normalizedText = $searchTerm->getNormalizedText();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            $searchTermFeedbackSearches = $this->searchTermFeedbackSearchDynamodbRepository->findBySearchTermNormalizedText($normalizedText);
            $feedbackSearches = array_map(
                static fn (SearchTermFeedbackSearch $searchTermFeedbackSearch) => new FeedbackSearch(
                    id: $searchTermFeedbackSearch->getFeedbackSearchId(),
                    // todo: add extra search terms
                    searchTerm: $searchTermFeedbackSearch->getSearchTerm(),
                    userId: $searchTermFeedbackSearch->getUserId(),
                    hasActiveSubscription: $searchTermFeedbackSearch->hasUserActiveSubscription(),
                    countryCode: $searchTermFeedbackSearch->getUserCountryCode(),
                    localeCode: $searchTermFeedbackSearch->getUserLocaleCode(),
                    messengerUserId: $searchTermFeedbackSearch->getMessengerUserId(),
                    telegramBotId: $searchTermFeedbackSearch->getTelegramBotId(),
                ),
                $searchTermFeedbackSearches
            );
            if ($withUsers) {
                $userIds = array_map(fn ($searchTermFeedbackSearch) => $searchTermFeedbackSearch->getUserId(), $searchTermFeedbackSearches);
                $users = $this->userRepository->findByIds($userIds);
                $userIdMap = array_combine(array_map(static fn (User $user) => $user->getId(), $users), $users);
                foreach ($searchTermFeedbackSearches as $idx => $searchTermFeedbackSearch) {
                    $feedbackSearches[$idx]->setUser($userIdMap[$searchTermFeedbackSearch->getUserId()]);
                }
            }
        } else {
            $feedbackSearches = $this->feedbackSearchRepository->findByNormalizedText($normalizedText, $withUsers, $maxResults);
        }

        $feedbackSearches = array_filter(
            $feedbackSearches,
            static fn (FeedbackSearch $feedbackSearch): bool => $searchTerm->getType() === SearchTermType::unknown
                || $feedbackSearch->getSearchTerm()->getType() === SearchTermType::unknown
                || $searchTerm->getType() === $feedbackSearch->getSearchTerm()->getType()
        );

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $maxResults, true);
    }
}
