<?php
declare(strict_types=1);

namespace App\Service\Util\String;

class MbUcFirster
{
    function mbUcFirst(string $input, string $encoding = null): string
    {
        $firstChar = mb_substr($input, 0, 1, $encoding);
        $then = mb_substr($input, 1, null, $encoding);

        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}