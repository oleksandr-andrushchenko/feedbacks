<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<FeedbackLookup>
 */
class FeedbackLookupDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, FeedbackLookup::class);
    }

    public function find(string $id): ?FeedbackLookup
    {
        return $this->getOne(['id' => $id]);
    }
}
