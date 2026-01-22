<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotPayment>
 * @method TelegramBotPaymentDoctrineRepository getDoctrine()
 * @property-read TelegramBotPaymentDoctrineRepository $doctrine
 * @method TelegramBotPaymentDynamodbRepository getDynamodb()
 * @property-read TelegramBotPaymentDynamodbRepository $dynamodb
 */
class TelegramBotPaymentRepository extends EntityRepository
{
}
