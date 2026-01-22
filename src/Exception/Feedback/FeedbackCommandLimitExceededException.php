<?php

declare(strict_types=1);

namespace App\Exception\Feedback;

use App\Exception\Exception;
use App\Model\Feedback\Command\FeedbackCommandLimit;
use Throwable;

class FeedbackCommandLimitExceededException extends Exception
{
    public function __construct(
        private readonly FeedbackCommandLimit $limit,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct('', $code, $previous);
    }

    public function getLimit(): FeedbackCommandLimit
    {
        return $this->limit;
    }
}
