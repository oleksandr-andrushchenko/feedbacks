<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationState;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Exception\ValidatorException;
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
        private readonly UserContactMessageCreator $messageCreator,
        private readonly ChooseActionTelegramChatSender $chooseActionChatSender,
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

        if ($help) {
            $query = $tg->view('contact_left_message_confirm_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryLeftMessageConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_LEFT_MESSAGE_CONFIRM_QUERIED);

        $message = $this->getLeftMessageConfirmQuery($tg, $help);

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
        ];

        if ($this->state->hasNotSkipHelpButton('left_message_confirm')) {
            $buttons[] = $tg->helpButton();
        }

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotLeftMessageConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionChatSender->sendActions($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('left_message_confirm');

            return $this->queryLeftMessageConfirm($tg, true);
        }
        if (!$tg->matchText($tg->yesButton()->getText())) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryLeftMessageConfirm($tg);
        }

        return $this->queryMessage($tg);
    }

    public function getMessageQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.message', domain: 'contact');

        if ($help) {
            $query = $tg->view('contact_message_help', [
                'query' => $query,
            ]);
        }

        return $query;
    }

    public function queryMessage(TelegramBotAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_MESSAGE_QUERIED);

        $message = $this->getMessageQuery($tg, $help);

        if ($this->state->hasNotSkipHelpButton('message')) {
            $buttons[] = $tg->helpButton();
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'contact');
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity)->reply($message);

        return $this->chooseActionChatSender->sendActions($tg);
    }

    public function gotMessage(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchText(null)) {
            $message = $tg->trans('reply.wrong');
            $message = $tg->wrongText($message);

            $tg->reply($message);

            return $this->queryMessage($tg);
        }
        if ($tg->matchText($tg->helpButton()->getText())) {
            $this->state->addSkipHelpButton('message');

            return $this->queryMessage($tg, true);
        }
        if ($tg->matchText($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        try {
            $this->messageCreator->createUserContactMessage(
                new UserContactMessageTransfer(
                    $tg->getBot()->getMessengerUser(),
                    $tg->getBot()->getMessengerUser()->getUser(),
                    $tg->getText(),
                    $tg->getBot()->getEntity()
                )
            );

            $tg->stopConversation($entity);

            $message = $tg->trans('reply.ok', domain: 'contact');
            $message = $tg->okText($message);

            return $this->chooseActionChatSender->sendActions($tg, $message);
        } catch (ValidatorException $exception) {
            $tg->reply($exception->getFirstMessage());

            return $this->queryMessage($tg);
        }
    }
}