<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodRepository;
use App\Repository\Telegram\Bot\TelegramBotPaymentRepository;

class TelegramBotPaymentService
{
    public function __construct(
        private readonly TelegramBotPaymentRepository $telegramBotPaymentRepository,
        private readonly TelegramBotPaymentMethodRepository $telegramBotPaymentMethodRepository,
        private readonly MessengerUserRepository $messengerUserRepository,
    )
    {
    }

    public function getMessengerUser(TelegramBotPayment $telegramBotPayment): MessengerUser
    {
        $messengerUser = $telegramBotPayment->getMessengerUser();

        if ($this->telegramBotPaymentRepository->getConfig()->isDynamodb()) {
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->find($telegramBotPayment->getMessengerUserId());
            $telegramBotPayment->setMessengerUser($messengerUser);
        }

        return $messengerUser;
    }

    public function getTelegramBotPaymentMethod(TelegramBotPayment $telegramBotPayment): TelegramBotPaymentMethod
    {
        $telegramBotPaymentMethod = $telegramBotPayment->getTelegramBotPaymentMethod();

        if ($this->telegramBotPaymentRepository->getConfig()->isDynamodb()) {
            if ($telegramBotPaymentMethod !== null) {
                return $telegramBotPaymentMethod;
            }
            $telegramBotPaymentMethod = $this->telegramBotPaymentMethodRepository->find($telegramBotPayment->getTelegramBotPaymentMethodId());
            $telegramBotPayment->setTelegramBotPaymentMethod($telegramBotPaymentMethod);
        }

        return $telegramBotPaymentMethod;
    }
}