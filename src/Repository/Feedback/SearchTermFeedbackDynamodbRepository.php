<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTermFeedback;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<SearchTermFeedback>
 */
class SearchTermFeedbackDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, SearchTermFeedback::class);
    }

    /**
     * @return array<SearchTermFeedback>
     */
    public function findBySearchTermNormalizedText(string $normalizedText): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('SEARCH_TERM_FEEDBACKS_BY_SEARCH_TERM_NORMALIZED_TEXT_CREATED')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_feedback_normalized_text_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $normalizedText,
                ])
        );
    }
}
