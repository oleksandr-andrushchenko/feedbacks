<?php
declare(strict_types=1);

namespace App\Exception\Feedback;

use App\Exception\Exception;
use Throwable;

class FeedbackSubscriptionPlanNotFoundException extends Exception
{
    public function __construct(string $plan, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" feedback subscription plan has not been found', $plan), $code, $previous);
    }
}
