<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\UserContactMessage;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<UserContactMessage>
 * @method UserContactMessageDoctrineRepository getDoctrine()
 * @property-read  UserContactMessageDoctrineRepository $doctrine
 * @method UserContactMessageDynamodbRepository getDynamodb()
 * @property-read  UserContactMessageDynamodbRepository $dynamodb
 */
class UserContactMessageRepository extends EntityRepository
{
}
