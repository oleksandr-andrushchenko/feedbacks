<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot as TelegramBotEntity;
use Longman\TelegramBot\Entities\WebhookInfo;

class TelegramBotWebhookInfoProvider
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
    )
    {
    }

    /**
     * @param TelegramBotEntity $entity
     * @return array
     */
    public function getTelegramWebhookInfo(TelegramBotEntity $entity): array
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($entity);
        /** @var WebhookInfo $info */
        $info = $bot->getWebhookInfo()->getResult();

        return [
            'url' => $info->getUrl(),
            'custom_certificate' => $info->getHasCustomCertificate(),
            'pending_update_count' => $info->getPendingUpdateCount(),
            'ip_address' => $info->getIpAddress(),
            'max_connections' => $info->getMaxConnections(),
            'allowed_updates' => $info->getAllowedUpdates(),
            'last_error_date' => $info->getLastErrorDate(),
            'last_error_message' => $info->getLastErrorMessage(),
            'last_synchronization_error_date' => $info->getLastSynchronizationErrorDate(),
        ];
    }
}