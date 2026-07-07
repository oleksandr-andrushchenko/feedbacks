<?php
declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\SearchFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackV2TelegramBotConversation;
use App\Service\Validator\ValidatorHelper;
use App\Validator\Feedback\SearchTermTransferConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SearchFeedbackTelegramBotConversationStateValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param SearchFeedbackTelegramBotConversationState $value
     * @param SearchFeedbackTelegramBotConversationStateConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof SearchFeedbackTelegramBotConversationState) {
            throw new UnexpectedValueException($value, SearchFeedbackTelegramBotConversationState::class);
        }

        if (!$constraint instanceof SearchFeedbackTelegramBotConversationStateConstraint) {
            throw new UnexpectedValueException($value, SearchFeedbackTelegramBotConversationStateConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('feedbacks.tg.search_validation');

        if ($value->getStep() >= SearchFeedbackV2TelegramBotConversation::STEP_DETAILS_QUERIED) {
            if ($value->getSearchTerms() === null) {
                return $helper->addMessage($constraint->searchTermNotBlankMessage);
            }
        }

        foreach (($value->getSearchTerms()?->getItemsAsArray() ?? []) as $searchTerm) {
            $this->context->getValidator()->validate($searchTerm, new SearchTermTransferConstraint());
        }

        return null;
    }
}