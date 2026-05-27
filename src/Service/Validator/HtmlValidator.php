<?php
declare(strict_types=1);

namespace App\Service\Validator;

class HtmlValidator
{
    public function validateHtml(string $text): bool
    {
        $text = '<div>' . $text . '</div>';
        $start = mb_strpos($text, '<');
        $end = mb_strrpos($text, '>', $start);

        $len = mb_strlen($text);

        if ($end === false) {
            $text = mb_substr($text, $start, $len - $start);
        } else {
            $text = mb_substr($text, $start);
        }

        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();

        simplexml_load_string($text);
        $isValid = libxml_get_last_error() === false;

        libxml_use_internal_errors($prev);

        return $isValid;
    }
}