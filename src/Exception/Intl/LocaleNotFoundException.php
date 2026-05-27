<?php
declare(strict_types=1);

namespace App\Exception\Intl;

use App\Exception\Exception;
use Throwable;

class LocaleNotFoundException extends Exception
{
    public function __construct(string $localeCode, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" locale has not been found', $localeCode), $code, $previous);
    }
}
