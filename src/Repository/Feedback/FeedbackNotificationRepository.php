<?php
declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackNotification;
use App\Repository\EntityRepository;

/**
 * @extends EntityRepository<FeedbackNotification>
 * @method FeedbackNotificationDynamodbRepository getDynamodb()
 * @property-read FeedbackNotificationDynamodbRepository $dynamodb
 */
class FeedbackNotificationRepository extends EntityRepository
{
}
