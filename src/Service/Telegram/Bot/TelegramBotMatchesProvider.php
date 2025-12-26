<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Repository\Telegram\Bot\TelegramBotRepository;

class TelegramBotMatchesProvider
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    /**
     * @param User $user
     * @param TelegramBot $bot
     * @return TelegramBot[]
     */
    public function getTelegramBotMatches(User $user, TelegramBot $bot): array
    {
        $bots = $this->telegramBotRepository->findPrimaryNonDeletedByGroup($bot->getGroup());

        if (count($bots) === 0) {
            return [];
        }

        $bots = array_combine(array_map(static fn (TelegramBot $bot): string => $bot->getId(), $bots), $bots);

        $points = [];

        foreach ($bots as $id => $bot) {
            $points[$id] = $this->calculateTelegramBotPoints($bot, $user);
        }

        $points = array_filter($points);

        if (count($points) === 0) {
            return [];
        }

        asort($points);

        $lastMaxPointsId = array_key_last($points);
        $maxPoints = $points[$lastMaxPointsId];

        $maxPointsIds = array_keys(array_filter($points, static fn (int $pts): bool => $pts === $maxPoints));
        $bots = array_filter($bots, static fn (TelegramBot $bot): bool => in_array($bot->getId(), $maxPointsIds, true));

        return array_values($bots);
    }

    public function calculateTelegramBotPoints(TelegramBot $bot, User $user): int
    {
        $points = 0;

        if ($user->getCountryCode() === null) {
            return 0;
        }

        if ($user->getCountryCode() !== $bot->getCountryCode()) {
            return 0;
        }

        $points += 1;

        if ($user->getLocaleCode() === $bot->getLocaleCode()) {
            $points += 1;
        }

        return $points;
    }
}