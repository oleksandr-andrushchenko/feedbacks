<?php
declare(strict_types=1);

namespace App\Service\Telegram\Channel\View;

use App\Entity\Telegram\TelegramChannel;
use App\Service\Telegram\TelegramLinkProvider;

class TelegramChannelLinkViewProvider
{
    public function __construct(
        private readonly TelegramLinkProvider $linkProvider,
    )
    {
    }

    public function getTelegramChannelLinkView(TelegramChannel $channel, bool $html = false): string
    {
        if (str_starts_with($channel->getUsername(), '+')) {
            $anchor = $channel->getName();
        } else {
            $anchor = '@' . $channel->getUsername();
        }

        if ($html) {
            $link = $this->linkProvider->getTelegramLink($channel->getUsername());

            return sprintf('<b><a href="%s">%s</a></b>', $link, $anchor);
        }

        return $anchor;
    }
}