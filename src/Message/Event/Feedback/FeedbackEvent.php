<?php
declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\Feedback;
use LogicException;

abstract readonly class FeedbackEvent
{
    private ?string $feedbackId;

    public function __construct(
        ?string $feedbackId = null,
        private ?Feedback $feedback = null,
    )
    {
        if ($feedbackId === null) {
            if ($this->feedback === null) {
                throw new LogicException('Either feedback id or feedback should be passed`');
            }

            $this->feedbackId = $this->feedback->getId();
        } else {
            $this->feedbackId = $feedbackId;
        }
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function getFeedbackId(): ?string
    {
        return $this->feedbackId;
    }

    public function __sleep(): array
    {
        return [
            'feedbackId',
        ];
    }
}
