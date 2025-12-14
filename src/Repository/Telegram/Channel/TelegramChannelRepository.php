<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramChannel>
 * @method TelegramChannelDoctrineRepository getDoctrine()
 * @property TelegramChannelDoctrineRepository $doctrine
 * @method TelegramChannelDynamodbRepository getDynamodb()
 * @property TelegramChannelDynamodbRepository $dynamodb
 * @method TelegramChannel[] findAll()
 * @method TelegramChannel|null findOneByUsername(string $username)
 * @method TelegramChannel|null findOneNonDeletedByUsername(string $username)
 * @method TelegramChannel|null findOnePrimaryNonDeletedByBot(TelegramBot $bot)
 * @method TelegramChannel|null findOnePrimaryNonDeletedByChannel(TelegramChannel $channel)
 * @method TelegramChannel[] findPrimaryNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode)
 */
class TelegramChannelRepository extends EntityRepository
{
}
