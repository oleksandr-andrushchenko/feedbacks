<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramBotGroupName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBot>
 */
class TelegramBotDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBot::class);
    }

    public function findOneByUsername(string $username): ?TelegramBot
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    public function findOneNonDeletedByUsername(string $username): ?TelegramBot
    {
        return $this->findOneBy([
            'username' => $username,
            'deletedAt' => null,
        ]);
    }

    public function findNonDeletedByGroup(TelegramBotGroupName $group): array
    {
        return $this->findBy([
            'group' => $group,
            'deletedAt' => null,
        ]);
    }

    public function findPrimaryNonDeletedByGroup(TelegramBotGroupName $group): array
    {
        return $this->findBy([
            'group' => $group,
            'primary' => true,
            'deletedAt' => null,
        ]);
    }

    public function findNonDeletedByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        return $this->findBy([
            'group' => $group,
            'countryCode' => $countryCode,
            'deletedAt' => null,
        ]);
    }

    public function findOnePrimaryNonDeletedByBot(TelegramBot $bot): ?TelegramBot
    {
        return $this->findOneBy([
            'group' => $bot->getGroup(),
            'countryCode' => $bot->getCountryCode(),
            'localeCode' => $bot->getLocaleCode(),
            'primary' => true,
            'deletedAt' => null,
        ]);
    }

    public function findPrimaryNonDeletedByGroupAndIds(TelegramBotGroupName $group, array $ids): array
    {
        return $this->findBy([
            'id' => $ids,
            'group' => $group,
            'primary' => true,
            'deletedAt' => null,
        ]);
    }
}
