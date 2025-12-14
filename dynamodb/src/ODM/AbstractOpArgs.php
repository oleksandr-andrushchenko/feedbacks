<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

abstract class AbstractOpArgs
{
    public function __construct(
        /** @var array<string, mixed> */
        protected array $args = [],
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return $this->args;
    }

    public function tableName(string $table): static
    {
        $this->args['TableName'] = $table;

        return $this;
    }

    public function key(array $key): static
    {
        $this->args['Key'] = $key;
        return $this;
    }

    public function indexName(string $index): static
    {
        $this->args['IndexName'] = $index;

        return $this;
    }

    public function filterExpression(array $expression): static
    {
        $this->args['FilterExpression'] = $expression;

        return $this;
    }

    public function projectionExpression(string $expression): static
    {
        $this->args['ProjectionExpression'] = $expression;

        return $this;
    }

    /**
     * @param array<string, string> $names
     */
    public function expressionAttributeNames(array $names): static
    {
        $this->args['ExpressionAttributeNames'] = $names;

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function expressionAttributeValues(array $values): static
    {
        $this->args['ExpressionAttributeValues'] = $values;

        return $this;
    }

    public function limit(int $limit): static
    {
        if (0 < $limit) {
            $this->args['Limit'] = $limit;
        }

        return $this;
    }

    public function select(string $select): static
    {
        $this->args['Select'] = $select;

        return $this;
    }

    public function consistentRead(bool $value = true): static
    {
        $this->args['ConsistentRead'] = $value;

        return $this;
    }

    public function returnConsumedCapacity(string $value): static
    {
        $this->args['ReturnConsumedCapacity'] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $startKey
     */
    public function exclusiveStartKey(array $startKey): static
    {
        $this->args['ExclusiveStartKey'] = $startKey;

        return $this;
    }
}
