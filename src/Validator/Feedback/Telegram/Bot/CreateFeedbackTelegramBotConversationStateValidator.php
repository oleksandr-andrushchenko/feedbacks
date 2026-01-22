<?php

declare(strict_types=1);

namespace App\Validator\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Validator\ValidatorHelper;
use App\Validator\Feedback\SearchTermTransferConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CreateFeedbackTelegramBotConversationStateValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorHelper $helper,
    )
    {
    }

    /**
     * @param CreateFeedbackTelegramBotConversationState $value
     * @param CreateFeedbackTelegramBotConversationStateConstraint $constraint
     * @return null
     */
    public function validate(mixed $value, Constraint $constraint): null
    {
        if (!$value instanceof CreateFeedbackTelegramBotConversationState) {
            throw new UnexpectedValueException($value, CreateFeedbackTelegramBotConversationState::class);
        }

        if (!$constraint instanceof CreateFeedbackTelegramBotConversationStateConstraint) {
            throw new UnexpectedValueException($value, CreateFeedbackTelegramBotConversationStateConstraint::class);
        }

        $helper = $this->helper->withContext($this->context)->withTranslationDomain('feedbacks.tg.create_validation');

        if ($value->getStep() > CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED) {
            if ($value->getSearchTerms() === null) {
                $helper->addMessage($constraint->searchTermsNotBlankMessage);
            }
        }

        foreach ($value->getSearchTerms() as $searchTerm) {
            $this->context->getValidator()->validate($searchTerm, new SearchTermTransferConstraint());
        }

        if ($value->getStep() > CreateFeedbackTelegramBotConversation::STEP_RATING_QUERIED) {
            if ($value->getRating() === null) {
                $helper->addMessage($constraint->ratingNotBlankMessage);
            }
        }

        if ($value->getDescription() !== null) {
            $descriptionLength = mb_strlen($value->getDescription());

            if ($descriptionLength === 0) {
                return $helper->addMessage($constraint->descriptionNotBlankMessage);
            }

            if ($descriptionLength < $constraint->descriptionMinLength) {
                $helper->addMessage($constraint->descriptionMinLengthMessage, [
                    'min_length' => $constraint->descriptionMinLength,
                    'value' => $value->getDescription(),
                ]);
            }

            if ($descriptionLength > $constraint->descriptionMaxLength) {
                $helper->addMessage($constraint->descriptionMaxLengthMessage, [
                    'max_length' => $constraint->descriptionMaxLength,
                    'value' => $value->getDescription(),
                ]);
            }
        }

        return null;
    }
}