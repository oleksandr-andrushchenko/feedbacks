<?php
declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use LogicException;

readonly abstract class FeedbackLookupEvent
{
    private ?string $lookupId;

    public function __construct(
        private ?FeedbackLookup $lookup = null,
        ?string $lookupId = null,
    )
    {
        if ($lookupId === null) {
            if ($this->lookup === null) {
                throw new LogicException('Either lookup id or lookup should be passed`');
            }

            $this->lookupId = $this->lookup->getId();
        } else {
            $this->lookupId = $lookupId;
        }
    }

    public function getFeedbackLookup(): ?FeedbackLookup
    {
        return $this->lookup;
    }

    public function getFeedbackLookupId(): ?string
    {
        return $this->lookupId;
    }

    public function __sleep(): array
    {
        return [
            'lookupId',
        ];
    }
}
