<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Entity\User\User;
use App\Repository\Telegram\Channel\TelegramChannelRepository;

class TelegramChannelMatchesProvider
{
    public function __construct(
        private readonly TelegramChannelRepository $telegramChannelRepository,
    )
    {
    }

    /**
     * @param User $user
     * @param TelegramBot $bot
     * @return TelegramChannel[]
     */
    public function getCachedTelegramChannelMatches(User $user, TelegramBot $bot): array
    {
        static $cache = [];

        $key = implode('-', [
            $bot->getGroup()->value,
            $bot->getCountryCode(),
            $user->getLevel1RegionId(),
        ]);

        if (!isset($cache[$key])) {
            $cache[$key] = $this->getTelegramChannelMatches($user, $bot);
        }

        return $cache[$key];
    }

    /**
     * @param User $user
     * @param TelegramBot $bot
     * @return TelegramChannel[]
     */
    public function getTelegramChannelMatches(User $user, TelegramBot $bot): array
    {
        $channels = $this->telegramChannelRepository->findPrimaryNonDeletedByGroupAndCountry($bot->getGroup(), $bot->getCountryCode());

        if (count($channels) === 0) {
            return [];
        }

        $channels = array_combine(array_map(static fn (TelegramChannel $channel) => $channel->getId(), $channels), $channels);

        $points = [];

        foreach ($channels as $id => $channel) {
            $points[$id] = $this->calculateTelegramChannelPoints($bot, $user, $channel);
        }

        $points = array_filter($points);

        if (count($points) === 0) {
            return [];
        }

        asort($points);

        $points = array_filter($points);

        $channels = array_filter($channels, static fn (TelegramChannel $channel): bool => array_key_exists($channel->getId(), $points));

        return array_values($channels);
    }

    public function calculateTelegramChannelPoints(TelegramBot $bot, User $user, TelegramChannel $channel): int
    {
        $points = 0;

        if ($bot->getCountryCode() !== $channel->getCountryCode()) {
            return 0;
        }

        $points += 1;

        if ($user->getCountryCode() !== null && $user->getCountryCode() !== $bot->getCountryCode()) {
            if ($channel->getLevel1RegionId() !== null) {
                return 0;
            }

            return $points;
        }

        if ($user->getLevel1RegionId() === null && $channel->getLevel1RegionId() !== null) {
            return 0;
        }

        if ($channel->getLevel1RegionId() === null) {
            return $points;
        }

        if ($user->getLevel1RegionId() !== $channel->getLevel1RegionId()) {
            return 0;
        }

        $points += 2;

        return $points;
    }
}