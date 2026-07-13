<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Exception\ValidatorException;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\ContactOptionsFactory;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\User\UserContactMessageCreator;
use App\Transfer\User\UserContactMessageTransfer;

class ContactTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_LEFT_MESSAGE_CONFIRM_QUERIED = 10;
    public const int STEP_MESSAGE_QUERIED = 20;
    public const int STEP_CANCEL_PRESSED = 30;

    public function __construct(
        private readonly UserContactMessageCreator $userContactMessageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly ContactOptionsFactory $contactOptionsFactory,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED => $this->gotLeftMessageConfirm($tg, $entity),
            self::STEP_MESSAGE_QUERIED => $this->gotMessage($tg, $entity),
        };
    }

    private function start(TelegramBotAwareHelper $tg): ?string
    {
        $contacts = $this->contactOptionsFactory->createContactOptionsByTelegramBot($tg->getBot()->getEntity());

        $message = $tg->view('contact', [
            'contacts' => $contacts,
        ]);

        $tg->reply($message, protectContent: true);

        return $this->queryLeftMessageConfirm($tg);
    }

    private function queryLeftMessageConfirm(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED);

        $message = $tg->t('left_message_confirm', [], 'contact');
        $message = $tg->queryText($message);
        $message .= $tg->queryTipText($tg->useText(false));

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
            $tg->cancelButton(),
        ];

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotLeftMessageConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryLeftMessageConfirm($tg);
        }

        return $this->queryMessage($tg);
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->t('canceled', [], 'contact');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    private function queryMessage(TelegramBotAwareHelper $tg): ?string
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

        $message = $tg->t('input_message', [], 'contact');
        $message = $tg->queryText($message);
        $message .= $tg->queryTipText($tg->useText(true));

        $buttons = [
            $tg->cancelButton(),
        ];

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotMessage(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryMessage($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        try {
            $this->userContactMessageCreator->createUserContactMessage(
                new UserContactMessageTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $tg->getBot()->getUser(),
                    $tg->getText()->getRawValue(),
                    $tg->getBot()->getEntity()
                )
            );

            $tg->stopConversation($entity);

            $message = $tg->t('ok', [], 'contact');
            $message = $tg->okText($message);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($exception->getFirstMessage());

            return $this->queryMessage($tg);
        }
    }
}