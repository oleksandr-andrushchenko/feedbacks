<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Enum\Telegram\TelegramBotGroupName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChannel>
 *
 * @method TelegramChannel|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramChannel|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramChannel[]    findAll()
 * @method TelegramChannel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChannel::class);
    }

    public function findAnyOneByUsername(string $username): ?TelegramChannel
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    public function findOneByUsername(string $username): ?TelegramChannel
    {
        return $this->findOneBy([
            'username' => $username,
            'deletedAt' => null,
        ]);
    }

    public function findOnePrimaryByBot(TelegramBot $bot): ?TelegramChannel
    {
        return $this->findOneBy([
            'group' => $bot->getGroup(),
            'countryCode' => $bot->getCountryCode(),
            'localeCode' => $bot->getLocaleCode(),
            'level1RegionId' => null,
            'primary' => true,
            'deletedAt' => null,
        ]);
    }

    public function findOnePrimaryByChannel(TelegramChannel $channel): ?TelegramChannel
    {
        return $this->findOneBy([
            'group' => $channel->getGroup(),
            'countryCode' => $channel->getCountryCode(),
            'localeCode' => $channel->getLocaleCode(),
            'level1RegionId' => $channel->getLevel1RegionId(),
            'primary' => true,
            'deletedAt' => null,
        ]);
    }

    /**
     * @param TelegramBotGroupName $group
     * @param string $countryCode
     * @return TelegramChannel[]
     */
    public function findPrimaryByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        return $this->findBy([
            'group' => $group,
            'countryCode' => $countryCode,
            'primary' => true,
            'deletedAt' => null,
        ]);
    }
}
