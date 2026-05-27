<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\User;

class TelegramBotUserProvider
{
    public function getTelegramUserByUpdate(Update $update): ?User
    {
        return $this->getTelegramFromHolderByUpdate($update)?->getFrom();
    }

    public function getTelegramFromHolderByUpdate(Update $update): ?Entity
    {
        $updateMethods = [
            'getMessage',
            'getEditedMessage',
            'getChannelPost',
            'getEditedChannelPost',
            'getInlineQuery',
            'getChosenInlineResult',
            'getCallbackQuery',
            'getPreCheckoutQuery',
            'getMyChatMember',
        ];
        foreach ($updateMethods as $updateMethod) {
            $object = call_user_func([$update, $updateMethod]);

            if ($object === null) {
                continue;
            }

            return $object;
        }

        return null;
    }
}