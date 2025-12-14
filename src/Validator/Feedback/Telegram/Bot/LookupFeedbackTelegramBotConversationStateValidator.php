<?php

declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\LookupFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupFeedbackTelegramBotConversation;
use App\Service\Validator\ValidatorHelper;
use App\Validator\Feedback\SearchTermTransferConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class LookupFeedbackTelegramBotConversationStateValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param LookupFeedbackTelegramBotConversationState $value
     * @param LookupFeedbackTelegramBotConversationStateConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof LookupFeedbackTelegramBotConversationState) {
            throw new UnexpectedValueException($value, LookupFeedbackTelegramBotConversationState::class);
        }

        if (!$constraint instanceof LookupFeedbackTelegramBotConversationStateConstraint) {
            throw new UnexpectedValueException($value, LookupFeedbackTelegramBotConversationStateConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('feedbacks.tg.lookup_validation');

        if ($value->getStep() > LookupFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED) {
            if ($value->getSearchTerm() === null) {
                return $helper->addMessage($constraint->searchTermNotBlankMessage);
            }

            $this->context->getValidator()->validate($value->getSearchTerm(), new SearchTermTransferConstraint());
        }

        return null;
    }
}