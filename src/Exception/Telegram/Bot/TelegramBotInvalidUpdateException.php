<?php
declare(strict_types=1);

namespace App\Exception\Telegram\Bot;

use App\Exception\Exception;
use Throwable;

class TelegramBotInvalidUpdateException extends Exception
{
    public function __construct(string $content, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid telegram update "%s" content', $content), $code, $previous);
    }
}