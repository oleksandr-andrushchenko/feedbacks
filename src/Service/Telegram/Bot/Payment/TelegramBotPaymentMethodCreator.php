<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Payment;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Exception\Intl\CurrencyNotFoundException;
use App\Service\IdGenerator;
use App\Service\Intl\CurrencyProvider;
use App\Service\ORM\EntityManager;
use App\Transfer\Telegram\TelegramBotPaymentMethodTransfer;

class TelegramBotPaymentMethodCreator
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManager $entityManager,
    )
    {
    }

    /**
     * @param TelegramBotPaymentMethodTransfer $transfer
     * @return TelegramBotPaymentMethod
     * @throws CurrencyNotFoundException
     */
    public function createTelegramPaymentMethod(TelegramBotPaymentMethodTransfer $transfer): TelegramBotPaymentMethod
    {
        $currencyCodes = $transfer->getCurrencies();
        foreach ($currencyCodes as $currencyCode) {
            if (!$this->currencyProvider->hasCurrency($currencyCode)) {
                throw new CurrencyNotFoundException($currencyCode);
            }
        }

        $paymentMethod = new TelegramBotPaymentMethod(
            $this->idGenerator->generateId(),
            $transfer->getBot(),
            $transfer->getName(),
            $transfer->getToken(),
            $currencyCodes,
        );
        $this->entityManager->persist($paymentMethod);

        return $paymentMethod;
    }
}