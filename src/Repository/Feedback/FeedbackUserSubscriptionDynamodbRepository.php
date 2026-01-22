<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<FeedbackUserSubscription>
 */
class FeedbackUserSubscriptionDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, FeedbackUserSubscription::class);
    }

    public function findByMessengerUser(MessengerUser $messengerUser): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('FEEDBACK_USER_SUBSCRIPTIONS_BY_MESSENGER_USER')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'feedback_user_subscription_messenger_user_id_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $messengerUser->getId(),
                ])
        );
    }

    public function findByUser(User $user): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('FEEDBACK_USER_SUBSCRIPTIONS_BY_USER')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'feedback_user_subscription_user_id_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $user->getId(),
                ])
        );
    }
}
