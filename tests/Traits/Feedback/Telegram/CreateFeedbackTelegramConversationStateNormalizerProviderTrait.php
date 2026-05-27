<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback\Telegram;

use App\Serializer\Feedback\Telegram\Bot\CreateFeedbackTelegramBotConversationStateNormalizer;

trait CreateFeedbackTelegramConversationStateNormalizerProviderTrait
{
    public function getCreateFeedbackTelegramConversationStateNormalizer(): CreateFeedbackTelegramBotConversationStateNormalizer
    {
        return static::getContainer()->get('app.telegram_bot_conversation_state_create_feedback_normalizer');
    }
}