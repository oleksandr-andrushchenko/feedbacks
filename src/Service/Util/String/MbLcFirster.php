<?php
declare(strict_types=1);

namespace App\Service\Util\String;

class MbLcFirster
{
    function mbLcFirst(string $input, string $encoding = null): string
    {
        $firstChar = mb_substr($input, 0, 1, $encoding);
        $then = mb_substr($input, 1, null, $encoding);

        return mb_strtolower($firstChar, $encoding) . $then;
    }
}