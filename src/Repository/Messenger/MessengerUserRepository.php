<?php

declare(strict_types=1);

namespace App\Repository\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<User>
 * @method MessengerUserDoctrineRepository getDoctrine()
 * @property MessengerUserDoctrineRepository $doctrine
 * @method MessengerUserDynamodbRepository getDynamodb()
 * @property MessengerUserDynamodbRepository $dynamodb
 * @method MessengerUser|null find(string $id)
 * @method MessengerUser|null findOneByMessengerAndIdentifier(Messenger $messenger, string $identifier)
 * @method MessengerUser|null findOneByMessengerAndUsername(Messenger $messenger, string $username)
 * @method MessengerUser[] findByUser(User $user)
 */
class MessengerUserRepository extends EntityRepository
{
}
