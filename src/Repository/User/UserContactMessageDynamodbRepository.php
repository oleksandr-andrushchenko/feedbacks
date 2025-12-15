<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\UserContactMessage;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<UserContactMessage>
 */
class UserContactMessageDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, UserContactMessage::class);
    }
}
