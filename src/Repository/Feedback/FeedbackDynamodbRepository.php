<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\Feedback;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;

/**
 * @extends EntityRepository<Feedback>
 */
class FeedbackDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, Feedback::class);
    }

    public function find(string $id): ?Feedback
    {
        return $this->getOne(['id' => $id]);
    }
}
