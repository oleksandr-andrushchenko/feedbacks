<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<FeedbackUserSubscription>
 * @method FeedbackUserSubscriptionDoctrineRepository getDoctrine()
 * @property FeedbackUserSubscriptionDoctrineRepository $doctrine
 * @method FeedbackUserSubscriptionDynamodbRepository getDynamodb()
 * @property FeedbackUserSubscriptionDynamodbRepository $dynamodb
 * @method array<FeedbackUserSubscription> findByMessengerUser(MessengerUser $messengerUser)
 * @method array<FeedbackUserSubscription> findByUser(User $user)
 */
class FeedbackUserSubscriptionRepository extends EntityRepository
{
}
