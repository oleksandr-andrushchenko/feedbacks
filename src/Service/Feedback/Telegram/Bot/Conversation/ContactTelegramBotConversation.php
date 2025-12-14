<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Exception\ValidatorException;
use App\Model\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Service\ContactOptionsFactory;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\User\UserContactMessageCreator;
use App\Transfer\User\UserContactMessageTransfer;

class ContactTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_LEFT_MESSAGE_CONFIRM_QUERIED = 10;
    public const STEP_MESSAGE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        private readonly UserContactMessageCreator $userContactMessageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly ContactOptionsFactory $contactOptionsFactory,
    )
    {
        parent::__construct(new CreateFeedbackTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED => $this->gotLeftMessageConfirm($tg, $entity),
            self::STEP_MESSAGE_QUERIED => $this->gotMessage($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        $contacts = $this->contactOptionsFactory->createContactOptionsByTelegramBot($tg->getBot()->getEntity());

        $message = $tg->view('contact', [
            'contacts' => $contacts,
        ]);

        $tg->reply($message, protectContent: true);

        return $this->queryLeftMessageConfirm($tg);
    }

    public function getLeftMessageConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.left_message_confirm', domain: 'contact');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('contact_left_message_confirm_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryLeftMessageConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED);

        $message = $this->getLeftMessageConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotLeftMessageConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryLeftMessageConfirm($tg, true);
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

    public function getMessageQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.message', domain: 'contact');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('contact_message_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(true));
        }

        return $query;
    }

    public function queryMessage(TelegramBotAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

        $message = $this->getMessageQuery($tg, $help);

        $buttons = [];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->trans('reply.canceled', domain: 'contact');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function gotMessage(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput(null)) {
            $tg->replyWrong(true);

            return $this->queryMessage($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryMessage($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        try {
            $this->userContactMessageCreator->createUserContactMessage(
                new UserContactMessageTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $tg->getBot()->getUser(),
                    $tg->getInput(),
                    $tg->getBot()->getEntity()
                )
            );

            $tg->stopConversation($entity);

            $message = $tg->trans('reply.ok', domain: 'contact');
            $message = $tg->okText($message);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($exception->getFirstMessage());

            return $this->queryMessage($tg);
        }
    }
}