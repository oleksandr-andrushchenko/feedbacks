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
 */
class TelegramChannelRepository extends EntityRepository
{
    /**
     * @param TelegramBotGroupName $group
     * @param string $countryCode
     * @return TelegramChannel[]
     */
    public function findPrimaryNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        if ($this->getConfig()->isDynamodb()) {
            return $this->getDynamodb()->findPrimaryNonDeletedByGroupAndCountry($group, $countryCode);
        }

        return $this->getDoctrine()->findPrimaryNonDeletedByGroupAndCountry($group, $countryCode);
    }
}
