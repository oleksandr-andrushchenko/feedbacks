<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotConversation>
 * @method TelegramBotConversationDoctrineRepository getDoctrine()
 * @property TelegramBotConversationDoctrineRepository $doctrine
 * @method TelegramBotConversationDynamodbRepository getDynamodb()
 * @property TelegramBotConversationDynamodbRepository $dynamodb
 * @method TelegramBotConversation|null findOneNonDeletedByHash(string $hash)
 */
class TelegramBotConversationRepository extends EntityRepository
{
}
