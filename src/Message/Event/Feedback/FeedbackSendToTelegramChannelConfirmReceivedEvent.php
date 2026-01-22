<?php

declare(strict_types=1);

namespace App\Message\Event\Feedback;

use App\Entity\Feedback\Feedback;

/**
 * @see FeedbackSendToTelegramChannelConfirmReceivedEventHandler
 */
readonly class FeedbackSendToTelegramChannelConfirmReceivedEvent extends FeedbackEvent
{
    public function __construct(
        ?string $feedbackId = null,
        ?Feedback $feedback = null,
        private bool $addTime = false,
        private bool $notifyUser = false
    )
    {
        parent::__construct($feedbackId, $feedback);
    }

    public function addTime(): bool
    {
        return $this->addTime;
    }

    public function notifyUser(): bool
    {
        return $this->notifyUser;
    }

    public function __sleep(): array
    {
        return array_merge(parent::__sleep(), [
            'addTime',
            'notifyUser',
        ]);
    }
}
