<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<TelegramBotConversation>
 */
class TelegramBotConversationDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotConversation::class);
    }

    public function findOneNonDeletedByHash(string $hash): ?TelegramBotConversation
    {
        return $this->queryOne(
            (new QueryArgs())
                ->keyConditionExpression([
                    '#pk = :pk',
                    'begins_with(#sk, :sk)',
                ])
                ->filterExpression([
                    'attribute_not_exists(#deletedAt)',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'pk',
                    '#sk' => 'sk',
                    '#deletedAt' => 'deleted_at',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'TELEGRAM_BOT_CONVERSATION#' . $hash,
                    ':sk' => 'META#',
                ])
        );
    }
}
