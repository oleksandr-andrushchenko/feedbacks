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
        private readonly FeedbackSearchService $feedbackSearchService,
    )
    {
    }

    /**
     * @return array<FeedbackSearch>
     */
    public function searchFeedbackSearches(SearchTerm $searchTerm, bool $withUsers = false, int $maxResults = 20): array
    {
        $normalizedText = $searchTerm->getNormalizedText();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            $searchTermFeedbackSearches = $this->searchTermFeedbackSearchDynamodbRepository->findBySearchTermNormalizedText($normalizedText);

            $feedbackSearches = [];
            foreach ($searchTermFeedbackSearches as $searchTermFeedbackSearch) {
                $feedbackSearchId = $searchTermFeedbackSearch->getFeedbackSearchId();
                if (!isset($feedbackSearches[$feedbackSearchId])) {
                    $feedbackSearches[$feedbackSearchId] = new FeedbackSearch(
                        id: $feedbackSearchId,
                        hasActiveSubscription: $searchTermFeedbackSearch->hasUserActiveSubscription(),
                        countryCode: $searchTermFeedbackSearch->getUserCountryCode(),
                        localeCode: $searchTermFeedbackSearch->getUserLocaleCode(),
                        userId: $searchTermFeedbackSearch->getUserId(),
                        messengerUserId: $searchTermFeedbackSearch->getMessengerUserId(),
                        telegramBotId: $searchTermFeedbackSearch->getTelegramBotId(),
                        createdAt: $searchTermFeedbackSearch->getCreatedAt(),
                    );
                }
                $feedbackSearches[$feedbackSearchId]->setSearchTerm($searchTermFeedbackSearch->getSearchTerm());
            }

            if ($withUsers) {
                $userIds = array_unique(array_map(static fn (FeedbackSearch $feedbackSearch) => $feedbackSearch->getUserId(), $feedbackSearches));
                $users = $this->userRepository->findByIds($userIds);
                $userIdMap = array_combine(array_map(static fn (User $user) => $user->getId(), $users), $users);
                foreach ($feedbackSearches as $feedbackSearch) {
                    $feedbackSearch->setUser($userIdMap[$feedbackSearch->getUserId()]);
                }
            }
        } else {
            $feedbackSearches = $this->feedbackSearchRepository->findByNormalizedText($normalizedText, $withUsers, $maxResults);
        }

        $feedbackSearches = array_filter(
            $feedbackSearches,
            function (FeedbackSearch $feedbackSearch) use ($searchTerm): bool {
                $searchTerm_ = $this->feedbackSearchService->getSearchTerm($feedbackSearch);

                return $searchTerm->getType() === SearchTermType::unknown
                    || $searchTerm_->getType() === SearchTermType::unknown
                    || $searchTerm->getType() === $searchTerm_->getType();
            }
        );

        $feedbackSearches = array_values($feedbackSearches);
        $feedbackSearches = array_reverse($feedbackSearches, true);

        return array_slice($feedbackSearches, 0, $maxResults, true);
    }
}
