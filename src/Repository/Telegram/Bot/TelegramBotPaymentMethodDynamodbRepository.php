<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityManager<TelegramBotPaymentMethod>
 */
class TelegramBotPaymentMethodDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, TelegramBotPaymentMethod::class);
    }

    public function find(string $id): ?TelegramBotPaymentMethod
    {
        return $this->getOne(['id' => $id]);
    }
}
