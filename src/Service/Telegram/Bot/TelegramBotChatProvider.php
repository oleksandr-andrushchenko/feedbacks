<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\Update;

class TelegramBotChatProvider
{
    public function getTelegramChatByUpdate(Update $update): ?Chat
    {
        if ($update->getCallbackQuery() !== null) {
            return $update->getCallbackQuery()->getMessage()?->getChat();
        }

        $updateMethods = [
            'getMessage',
            'getEditedMessage',
            'getMyChatMember',
            'getChatJoinRequest',
            'getChannelPost',
            'getSenderChat',
        ];
        foreach ($updateMethods as $updateMethod) {
            $object = call_user_func([$update, $updateMethod]);

            if ($object === null) {
                continue;
            }

            return $object->getChat();
        }

        return null;
    }
}