<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotUpdate;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotUpdate>
 * @method TelegramBotUpdateDoctrineRepository getDoctrine()
 * @property TelegramBotUpdateDoctrineRepository $doctrine
 * @method TelegramBotUpdateDynamodbRepository getDynamodb()
 * @property TelegramBotUpdateDynamodbRepository $dynamodb
 * @method TelegramBotUpdate|null findOneByUpdateId($updateId)
 */
class TelegramBotUpdateRepository extends EntityRepository
{
}
