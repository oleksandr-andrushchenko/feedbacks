<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTermFeedbackSearch;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<SearchTermFeedbackSearch>
 */
class SearchTermFeedbackSearchDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, SearchTermFeedbackSearch::class);
    }

    /**
     * @return array<SearchTermFeedbackSearch>
     */
    public function findBySearchTermNormalizedText(string $normalizedText): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('SEARCH_TERM_FEEDBACK_SEARCHES_BY_SEARCH_TERM_NORMALIZED_TEXT_CREATED')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_feedback_search_normalized_text_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $normalizedText,
                ])
        );
    }
}
