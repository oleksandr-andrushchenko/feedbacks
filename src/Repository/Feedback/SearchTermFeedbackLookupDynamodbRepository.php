<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTermFeedbackLookup;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<SearchTermFeedbackLookup>
 */
class SearchTermFeedbackLookupDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, SearchTermFeedbackLookup::class);
    }

    /**
     * @return array<SearchTermFeedbackLookup>
     */
    public function findBySearchTermNormalizedText(string $normalizedText): array
    {
        // todo:
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('SEARCH_TERM_FEEDBACK_LOOKUPS_BY_SEARCH_TERM_NORMALIZED_TEXT_CREATED')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_feedback_lookup_normalized_text_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $normalizedText,
                ])
        );
    }
}
