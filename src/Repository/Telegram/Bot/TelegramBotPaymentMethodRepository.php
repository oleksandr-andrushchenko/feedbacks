<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotPaymentMethod>
 * @method TelegramBotPaymentMethodDoctrineRepository getDoctrine()
 * @property-read TelegramBotPaymentMethodDoctrineRepository $doctrine
 * @method TelegramBotPaymentMethodDynamodbRepository getDynamodb()
 * @property-read TelegramBotPaymentMethodDynamodbRepository $dynamodb
 * @method TelegramBotPaymentMethod|null find(string $id)
 */
class TelegramBotPaymentMethodRepository extends EntityRepository
{
}
