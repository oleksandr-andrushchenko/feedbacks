<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<TelegramBotPayment>
 */
class TelegramBotPaymentDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotPayment::class);
    }

    public function findOneByUuid(string $uuid): ?TelegramBotPayment
    {
        return $this->getOne(['uuid' => $uuid]);
    }
}
