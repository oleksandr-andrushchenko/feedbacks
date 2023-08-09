<?php

declare(strict_types=1);

namespace App\Exception\Telegram;

use App\Exception\Exception;
use Throwable;

class TelegramOptionsNotFoundException extends Exception
{
    public function __construct(string $username, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" telegram bot options has not been found', $username), $code, $previous);
    }
}