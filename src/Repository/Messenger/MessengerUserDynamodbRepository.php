<?php

declare(strict_types=1);

namespace App\Repository\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<MessengerUser>
 */
class MessengerUserDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, MessengerUser::class);
    }

    public function find(string $id): ?MessengerUser
    {
        return $this->getOne(['id' => $id]);
    }

    public function findOneByMessengerAndIdentifier(Messenger $messenger, string $identifier): ?MessengerUser
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('MESSENGER_USERS_BY_MESSENGER_IDENTIFIER')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'messenger_user_messenger_identifier_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $messenger->value . '#' . $identifier,
                ])
        );
    }

    public function findOneByMessengerAndUsername(Messenger $messenger, string $username): ?MessengerUser
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('MESSENGER_USERS_BY_MESSENGER_USERNAME')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'messenger_user_messenger_username_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $messenger->value . '#' . $username,
                ])
        );
    }

    public function findByUser(User $user): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('MESSENGER_USERS_BY_USER')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'messenger_user_user_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $user->getId(),
                ])
        );
    }
}
