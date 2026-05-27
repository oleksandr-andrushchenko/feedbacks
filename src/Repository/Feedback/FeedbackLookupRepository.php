<?php
declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\User\User;
use App\Repository\EntityRepository;
use DateTimeInterface;

/**
 * @extends EntityRepository<FeedbackLookup>
 * @method FeedbackLookupDoctrineRepository getDoctrine()
 * @property-read FeedbackLookupDoctrineRepository $doctrine
 * @method FeedbackLookupDynamodbRepository getDynamodb()
 * @property-read FeedbackLookupDynamodbRepository $dynamodb
 * @method FeedbackLookup|null find(string $id)
 * @method FeedbackLookup|null findOneLast()
 * @method array<FeedbackLookup> findByNormalizedText(string $normalizeText, int $maxResults = 100)
 * @method int countByUserAndFromWithoutActiveSubscription(User $user, DateTimeInterface $from)
 */
class FeedbackLookupRepository extends EntityRepository
{
}
