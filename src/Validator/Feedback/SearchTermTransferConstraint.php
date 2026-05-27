<?php
declare(strict_types=1);

namespace App\Validator\Feedback;

use Symfony\Component\Validator\Constraint;

class SearchTermTransferConstraint extends Constraint
{
    public string $textNotBlankMessage = 'text.not_blank';

    public string $textSingleLineMessage = 'text.single_line';

    public string $textAllowedChars = ';*^$!~';
    public string $textAllowedCharsMessage = 'text.allowed_chars';

    public string $textEmojiRegex = '/([*#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?(?>\\xEF\\xB8\\x8F)?|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])(?>\\xEF\\xB8\\x8F)?|\\xF0\\x9F(?>[\\x80-\\x86].(?>\\xEF\\xB8\\x8F)?|\\x87.\\xF0\\x9F\\x87.|..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?|(((?<zwj>\\xE2\\x80\\x8D)\\xE2\\x9D\\xA4\\xEF\\xB8\\x8F\k<zwj>\\xF0\\x9F..(\k<zwj>\\xF0\\x9F\\x91.)?|(\\xE2\\x80\\x8D\\xF0\\x9F\\x91.){2,3}))?))/';
    public string $textEmojiMessage = 'text.emoji';

    public int $textMinLength = 2;
    public string $textMinLengthMessage = 'text.min_length';

    public int $textMaxLength = 256;
    public string $textMaxLengthMessage = 'text.max_length';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'app.feedback_search_term_validator';
    }
}