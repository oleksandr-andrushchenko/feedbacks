<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotGlobalRequestSecondRateLimit;
use DateTimeImmutable;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\UpdateArgs;

/**
 * @extends EntityRepository<TelegramBotGlobalRequestSecondRateLimit>
 */
class TelegramBotGlobalRequestSecondRateLimitDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotGlobalRequestSecondRateLimit::class);
    }

    public function incrementCountBySecond(int $second): TelegramBotGlobalRequestSecondRateLimit
    {
        $args = (new UpdateArgs())
            ->updateExpression('
                SET #second = if_not_exists(#second, :second),
                #expireAt = if_not_exists(#expireAt, :expireAt)
                ADD #count :countInc
            ')
            ->expressionAttributeNames([
                '#count' => 'count',
                '#second' => 'second',
                '#expireAt' => 'expire_at',
            ])
            ->expressionAttributeValues([
                ':countInc' => 1,
                ':expireAt' => (new DateTimeImmutable())->setTimestamp(time() + 2)->format('c'),
                ':second' => $second,
            ])
        ;

        return $this->updateOneByQueryReturn($args, ['second' => $second]);
    }
}
