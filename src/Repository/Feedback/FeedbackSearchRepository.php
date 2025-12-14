<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\User\User;
use App\Repository\EntityRepository;
use DateTimeInterface;

/**
 * @extends EntityRepository<FeedbackSearch>
 * @method FeedbackSearchDoctrineRepository getDoctrine()
 * @property-read FeedbackSearchDoctrineRepository $doctrine
 * @method FeedbackSearchDynamodbRepository getDynamodb()
 * @property-read FeedbackSearchDynamodbRepository $dynamodb
 * @method FeedbackSearch|null findOneLast()
 * @method int countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from)
 * @method array<FeedbackSearch> findByNormalizedText(string $normalizeText, bool $withUsers = false, int $maxResults = 100)
 */
class FeedbackSearchRepository extends EntityRepository
{
}
