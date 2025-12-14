<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use DateTimeInterface;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\QueryArgs;

/**
 * @extends EntityRepository<SearchTerm>
 */
class SearchTermDynamodbRepository extends EntityRepository
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, SearchTerm::class);
    }

    public function findByNormalizedText(string $normalizedText, int $maxResults = 100): array
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('SEARCH_TERMS_BY_NORMALIZED_TEXT')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_normalized_text_pk',
                ])
                ->expressionAttributeValues([
                    ':pk' => $normalizedText,
                ])
                ->limit($maxResults)
        );
    }

    public function findByIds(array $ids): array
    {
        return $this->getMany(array_map(static fn (string $id): array => ['id' => $id], $ids));
    }

    public function findOneByNormalizedTextTypeText(string $normalizedText, SearchTermType $type, string $text): ?SearchTerm
    {
        return $this->queryOne(
            (new QueryArgs())
                ->indexName('SEARCH_TERMS_BY_NORMALIZED_TEXT')
                ->keyConditionExpression([
                    '#pk = :pk',
                ])
                ->filterExpression([
                    '#type' => ':type',
                    '#text' => ':text',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_normalized_text_pk',
                    '#type' => 't',
                    '#text' => 'txt',
                ])
                ->expressionAttributeValues([
                    ':pk' => $normalizedText,
                    ':type' => $type->value,
                    ':text' => $text,
                ])
        );
    }

    public function findByPeriod(DateTimeInterface $from, DateTimeInterface $to): iterable
    {
        return $this->queryMany(
            (new QueryArgs())
                ->indexName('SEARCH_TERMS_BY_CREATED')
                ->keyConditionExpression([
                    '#pk = :pk',
                    '#sk BETWEEN :skFrom AND :skTo',
                ])
                ->expressionAttributeNames([
                    '#pk' => 'search_term_pk',
                    '#sk' => 'search_term_created_sk',
                ])
                ->expressionAttributeValues([
                    ':pk' => 'ST',
                    ':skFrom' => $from->format(DATE_ATOM),
                    ':skTo' => $to->format(DATE_ATOM),
                ])
        );
    }
}
