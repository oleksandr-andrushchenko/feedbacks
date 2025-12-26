<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramBot>
 * @method TelegramBotDoctrineRepository getDoctrine()
 * @property TelegramBotDoctrineRepository $doctrine
 * @method TelegramBotDynamodbRepository getDynamodb()
 * @property TelegramBotDynamodbRepository $dynamodb
 * @method TelegramBot[] findAll()
 * @method TelegramBot|null find(string $id)
 * @method TelegramBot|null findOneByUsername(string $username)
 * @method TelegramBot|null findOneNonDeletedByUsername(string $username)
 * @method TelegramBot[] findNonDeletedByGroup(TelegramBotGroupName $group)
 * @method TelegramBot[] findPrimaryNonDeletedByGroup(TelegramBotGroupName $group)
 * @method TelegramBot[] findNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode)
 * @method TelegramBot|null findOnePrimaryNonDeletedByBot(TelegramBot $bot)
 * @method TelegramBot[] findPrimaryNonDeletedByGroupAndIds(TelegramBotGroupName $group, array $ids)
 */
class TelegramBotRepository extends EntityRepository
{
}
