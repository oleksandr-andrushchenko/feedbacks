<?php
declare(strict_types=1);

namespace App\Validator\User;

use Symfony\Component\Validator\Constraint;

class UserContactMessageTransferConstraint extends Constraint
{
    public string $textNotBlankMessage = 'text.not_blank';

    public int $textMinLength = 5;
    public string $textMinLengthMessage = 'text.min_length';

    public int $textMaxLength = 2048;
    public string $textMaxLengthMessage = 'text.max_length';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'app.user_contact_message_validator';
    }
}