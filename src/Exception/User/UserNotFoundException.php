<?php
declare(strict_types=1);

namespace App\Exception\User;

use App\Exception\Exception;
use Throwable;

class UserNotFoundException extends Exception
{
    public function __construct(string $userId, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" user has not been found', $userId), $code, $previous);
    }
}
