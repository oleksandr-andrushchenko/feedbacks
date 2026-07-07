<?php
declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\User;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<User>
 * @method UserDynamodbRepository getDynamodb()
 * @property UserDynamodbRepository $dynamodb
 * @method User|null find(string $id)
 * @method array<User> findByIds(array<string> $ids)
 */
class UserRepository extends EntityRepository
{
}
