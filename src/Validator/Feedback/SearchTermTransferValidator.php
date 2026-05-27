<?php
declare(strict_types=1);

namespace App\Validator\Feedback;

use App\Service\Validator\ValidatorHelper;
use App\Transfer\Feedback\SearchTermTransfer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SearchTermTransferValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param SearchTermTransfer $value
     * @param SearchTermTransferConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof SearchTermTransfer) {
            throw new UnexpectedValueException($value, SearchTermTransfer::class);
        }

        if (!$constraint instanceof SearchTermTransferConstraint) {
            throw new UnexpectedValueException($value, SearchTermTransferConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('feedbacks.search_term_validation');

        $text = $value->getText();

        $textLength = mb_strlen($value->getText());

        if ($textLength === 0) {
            return $helper->addMessage($constraint->textNotBlankMessage);
        }

        if (preg_match('/\r\n|\r|\n/', $text)) {
            $helper->addMessage($constraint->textSingleLineMessage);
        }

        if (preg_match('/[' . $constraint->textAllowedChars . ']/', $value->getText()) === 1) {
            return $helper->addMessage($constraint->textAllowedCharsMessage, [
                'chars' => $constraint->textAllowedChars,
            ]);
        }

        if (preg_match($constraint->textEmojiRegex, $value->getText()) === 1) {
            return $helper->addMessage($constraint->textEmojiMessage);
        }

        if ($textLength < $constraint->textMinLength) {
            $helper->addMessage($constraint->textMinLengthMessage, [
                'min_length' => $constraint->textMinLength,
                'value' => $text,
            ]);
        }

        if ($textLength > $constraint->textMaxLength) {
            $helper->addMessage($constraint->textMaxLengthMessage, [
                'max_length' => $constraint->textMaxLength,
                'value' => $text,
            ]);
        }

        return null;
    }
}