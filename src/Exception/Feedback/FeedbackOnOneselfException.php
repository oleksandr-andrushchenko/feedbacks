<?php
declare(strict_types=1);

namespace App\Exception\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Exception\Exception;
use Throwable;

class FeedbackOnOneselfException extends Exception
{
    public function __construct(
        MessengerUser $messengerUser,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(
            sprintf(
                'Feedback on oneself is forbidden "%s/%s"',
                $messengerUser->getUsername(),
                $messengerUser->getMessenger()->name
            ),
            $code,
            $previous
        );
    }
}