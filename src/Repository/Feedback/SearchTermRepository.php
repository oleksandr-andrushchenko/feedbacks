<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Repository\EntityRepository;
use DateTimeInterface;

/**
 * @extends EntityRepository<SearchTerm>
 * @method SearchTermDoctrineRepository getDoctrine()
 * @property SearchTermDoctrineRepository $doctrine
 * @method SearchTermDynamodbRepository getDynamodb()
 * @property SearchTermDynamodbRepository $dynamodb
 * @method SearchTerm|null find(string $id)
 * @method iterable<SearchTerm> findByIds(array<string> $ids)
 */
class SearchTermRepository extends EntityRepository
{
}
