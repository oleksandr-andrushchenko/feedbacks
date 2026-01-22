<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\User\User;
use App\Repository\EntityRepository;
use DateTimeInterface;

/**
 * @extends EntityRepository<Feedback>
 * @method FeedbackDoctrineRepository getDoctrine()
 * @property-read FeedbackDoctrineRepository $doctrine
 * @method FeedbackDynamodbRepository getDynamodb()
 * @property-read FeedbackDynamodbRepository $dynamodb
 * @method Feedback|null findOneLast()
 * @method Feedback|null find(string $id)
 * @method int countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from)
 * @method array<Feedback> findByNormalizedText(string $normalizedText, bool $withUsers = false, int $maxResults = 100)
 * @method array<Feedback> findBySearchTermIds(array<string> $searchTermIds, bool $withUsers = false, int $maxResults = 100)
 * @method array<Feedback> findUnpublishedByPeriod(DateTimeInterface $from, DateTimeInterface $to)
 */
class FeedbackRepository extends EntityRepository
{
}
