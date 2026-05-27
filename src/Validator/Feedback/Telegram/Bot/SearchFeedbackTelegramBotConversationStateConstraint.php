<?php
declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use Symfony\Component\Validator\Constraint;

class SearchFeedbackTelegramBotConversationStateConstraint extends Constraint
{
    public string $searchTermNotBlankMessage = 'search_term.not_blank';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'app.feedback_search_validator';
    }
}