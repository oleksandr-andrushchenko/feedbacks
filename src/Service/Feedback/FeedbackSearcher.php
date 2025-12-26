<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Feedback\SearchTermFeedback;
use App\Entity\User\User;
use App\Enum\Feedback\SearchTermType;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Feedback\SearchTermFeedbackDynamodbRepository;
use App\Repository\User\UserRepository;

class FeedbackSearcher
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly SearchTermFeedbackDynamodbRepository $searchTermFeedbackDynamodbRepository,
        private readonly UserRepository $userRepository,
        private readonly FeedbackService $feedbackService,
    )
    {
    }

    /**
     * @param SearchTerm $searchTerm
     * @param bool $withUsers
     * @param int $maxResults
     * @return array<Feedback>
     */
    public function searchFeedbacks(SearchTerm $searchTerm, bool $withUsers = false, int $maxResults = 20): array
    {
        $normalizedText = $searchTerm->getNormalizedText();

        if ($this->feedbackRepository->getConfig()->isDynamodb()) {
            $searchTermFeedbacks = $this->searchTermFeedbackDynamodbRepository->findBySearchTermNormalizedText($normalizedText);
            $feedbacks = array_map(
                static fn (SearchTermFeedback $searchTermFeedback) => new Feedback(
                    id: $searchTermFeedback->getFeedbackId(),
                    userId: $searchTermFeedback->getUserId(),
                    countryCode: $searchTermFeedback->getUserCountryCode(),
                    localeCode: $searchTermFeedback->getUserLocaleCode(),
                    hasActiveSubscription: $searchTermFeedback->hasUserActiveSubscription(),
                    messengerUserId: $searchTermFeedback->getMessengerUserId(),
                    // todo: add extra search terms
                    searchTermIds: [$searchTermFeedback->getSearchTermId()],
                    rating: $searchTermFeedback->getFeedbackRating(),
                    text: $searchTermFeedback->getFeedbackText(),
                ),
                $searchTermFeedbacks
            );
            if ($withUsers) {
                $userIds = array_map(fn ($searchTermFeedback) => $searchTermFeedback->getUserId(), $searchTermFeedbacks);
                $users = $this->userRepository->findByIds($userIds);
                $userIdMap = array_combine(array_map(static fn (User $user) => $user->getId(), $users), $users);
                foreach ($searchTermFeedbacks as $idx => $searchTermFeedback) {
                    $feedbacks[$idx]->setUser($userIdMap[$searchTermFeedback->getUserId()]);
                }
            }
            foreach ($searchTermFeedbacks as $idx => $searchTermFeedback) {
                $searchTerms = $searchTermFeedback->getExtraSearchTerms() ?? [];
                array_unshift($searchTerms, $searchTermFeedback->getSearchTerm());
                $feedbacks[$idx]->setSearchTerms($searchTerms);
            }
        } else {
            $feedbacks = $this->feedbackRepository->findByNormalizedText($normalizedText, $withUsers, $maxResults);
        }

        // todo: if search term type is unknown - need to make multi-searches with normalized search term type for each possible type
        // todo: for example: search term=+1 (561) 314-5672, its a phone number, stored as: 15613145672, but search with unknown type will give FALSE (+1 (561) 314-5672 === 15613145672)
        // todo: coz it wasnt parsed to selected search term type

        $feedbacks = array_filter($feedbacks, function (Feedback $feedback) use ($searchTerm): bool {
            foreach ($this->feedbackService->getSearchTerms($feedback) as $term) {
                if (strcmp(mb_strtolower($term->getNormalizedText()), mb_strtolower($searchTerm->getNormalizedText())) === 0) {
                    if (
                        $searchTerm->getType() !== SearchTermType::unknown
                        && $term->getType() !== SearchTermType::unknown
                        && $searchTerm->getType() !== $term->getType()
                    ) {
                        return false;
                    }

                    return true;
                }
            }

            return false;
        });

        $feedbacks = array_values($feedbacks);
        $feedbacks = array_reverse($feedbacks, true);

        return array_slice($feedbacks, 0, $maxResults, true);
    }
}
