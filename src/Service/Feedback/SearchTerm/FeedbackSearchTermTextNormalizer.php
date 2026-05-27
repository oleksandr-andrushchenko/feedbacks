<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

class FeedbackSearchTermTextNormalizer
{
    public function normalizeFeedbackSearchTermText(string $text): string
    {
        return trim($text);
        return mb_strtolower(
            trim(
                $text
            )
        );
    }
}
