<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotUpdate;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotUpdate>
 */
class TelegramBotUpdateDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotUpdate::class);
    }

    public function findOneByUpdateId($updateId): ?TelegramBotUpdate
    {
        return $this->getOne(['id' => $updateId]);
    }
}
