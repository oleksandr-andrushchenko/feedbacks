<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\SearchTerm;
use App\Service\IdGenerator;
use App\Service\Messenger\MessengerUserUpserter;
use App\Service\ORM\EntityManager;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermUpserter
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly MessengerUserUpserter $messengerUserUpserter,
        private readonly FeedbackSearchTermTextNormalizer $feedbackSearchTermTextNormalizer,
        private readonly IdGenerator $idGenerator,
    )
    {
    }

    // todo: use for id generations (for real upsert) (?)
    public function createSearchTermHash(string $normalizedText, int $type, string $text): string
    {
        return md5($normalizedText . '-' . $type . '-' . $text);
    }

    public function upsertSearchTerm(SearchTermTransfer $searchTermTransfer): SearchTerm
    {
        if ($searchTermTransfer->getMessengerUser() !== null) {
            $messengerUser = $this->messengerUserUpserter->upsertMessengerUser($searchTermTransfer->getMessengerUser());
        }

        $searchTerm = new SearchTerm(
            $this->idGenerator->generateId(),
            $searchTermTransfer->getText(),
            $this->feedbackSearchTermTextNormalizer->normalizeFeedbackSearchTermText(
                $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText()
            ),
            $searchTermTransfer->getType(),
            messengerUser: $messengerUser ?? null,
        );
        $this->entityManager->persist($searchTerm);

        return $searchTerm;
    }
}
