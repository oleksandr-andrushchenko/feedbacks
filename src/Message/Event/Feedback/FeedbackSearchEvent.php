<?php
declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use LogicException;

readonly abstract class FeedbackSearchEvent
{
    private ?string $searchId;

    public function __construct(
        private ?FeedbackSearch $search = null,
        ?string $searchId = null,
    )
    {
        if ($searchId === null) {
            if ($this->search === null) {
                throw new LogicException('Either search id or search should be passed`');
            }

            $this->searchId = $this->search->getId();
        } else {
            $this->searchId = $searchId;
        }
    }

    public function getFeedbackSearch(): ?FeedbackSearch
    {
        return $this->search;
    }

    public function getFeedbackSearchId(): ?string
    {
        return $this->searchId;
    }

    public function __sleep(): array
    {
        return [
            'searchId',
        ];
    }
}
