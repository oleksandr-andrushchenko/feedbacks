<?php
declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramChannel;
use DateTimeImmutable;

class TelegramChannelRemover
{
    public function removeTelegramChannel(TelegramChannel $channel): void
    {
        $channel->setDeletedAt(new DateTimeImmutable());
    }

    public function undoTelegramChannelRemove(TelegramChannel $channel): void
    {
        $channel->setDeletedAt(null);
    }

    public function telegramChannelRemoved(TelegramChannel $channel): bool
    {
        return $channel->getDeletedAt() !== null;
    }
}