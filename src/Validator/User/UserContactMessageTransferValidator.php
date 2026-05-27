<?php
declare(strict_types=1);

namespace App\Validator\User;

use App\Service\Validator\ValidatorHelper;
use App\Transfer\User\UserContactMessageTransfer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UserContactMessageTransferValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param UserContactMessageTransfer $value
     * @param UserContactMessageTransferConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof UserContactMessageTransfer) {
            throw new UnexpectedValueException($value, UserContactMessageTransfer::class);
        }

        if (!$constraint instanceof UserContactMessageTransferConstraint) {
            throw new UnexpectedValueException($value, UserContactMessageTransferConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('user_contact_message_validation');

        $text = $value->getText();

        $textLength = mb_strlen($value->getText());

        if ($textLength === 0) {
            return $helper->addMessage($constraint->textNotBlankMessage);
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