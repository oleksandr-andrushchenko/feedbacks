<?php
declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use Symfony\Component\Validator\Constraint;

class CreateFeedbackTelegramBotConversationStateConstraint extends Constraint
{
    public string $searchTermsNotBlankMessage = 'search_terms.not_blank';
    public string $ratingNotBlankMessage = 'rating.not_blank';

    public string $descriptionNotBlankMessage = 'description.not_blank';

    public int $descriptionMinLength = 5;
    public string $descriptionMinLengthMessage = 'description.min_length';

    public int $descriptionMaxLength = 2048;
    public string $descriptionMaxLengthMessage = 'description.max_length';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'app.feedback_create_validator';
    }
}