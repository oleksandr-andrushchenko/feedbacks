<?php
declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Tests\Fixtures;
use Longman\TelegramBot\Entities\Update;

trait TelegramBotUpdateFixtureProviderTrait
{
    protected function getTelegramBotUpdateFixture(int $updateId = null): Update
    {
        return $this->getTelegramUpdate($updateId);
    }

    protected function getTelegramMessageUpdateFixture(
        array $data,
        int $fromId = Fixtures::TELEGRAM_USER_ID_1,
        int $chatId = Fixtures::TELEGRAM_CHAT_ID_1,
        int $updateId = null
    ): Update
    {
        return $this->getTelegramUpdate($updateId, Update::TYPE_MESSAGE, array_merge($data, [
            'message_id' => mt_rand(100, 200),
            'from' => [
                'id' => $fromId,
                'is_bot' => false,
                'first_name' => 'Wild.s',
                'username' => 'wild_snowgirl',
                'language_code' => 'uk',
                'is_premium' => true,
            ],
            'chat' => [
                'id' => $chatId,
                'first_name' => 'Wild.s',
                'username' => 'wild_snowgirl',
                'type' => 'private',
            ],
            'date' => 1678120209,
            'entities' => [],
        ]));
    }

    protected function getTelegramUpdate(int $updateId = null, string $type = null, array $body = null): Update
    {
        $data = [
            'update_id' => $updateId === null ? mt_rand(100, 200) : $updateId,
        ];

        if ($type !== null && $body !== null) {
            $data[$type] = $body;
        }

        return new Update($data);
    }
}