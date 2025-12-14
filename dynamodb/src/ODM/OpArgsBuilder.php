<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Aws\DynamoDb\Marshaler;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class OpArgsBuilder
{
    public function __construct(
        protected NormalizerInterface $normalizer,
        protected Marshaler $marshaler,
    )
    {
    }

    /**
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function serialize(AbstractOpArgs $args): array
    {
        /** @var array<string, mixed> $normalizedArgs */
        $normalizedArgs = $this->normalizer->normalize($args->get());

        $serializedArgs = [];

        foreach ($normalizedArgs as $arg => $value) {
            if (in_array($arg, ['KeyConditionExpression', 'FilterExpression'])) {
                $value = join(' AND ', $value);
            }
            if (in_array($arg, ['ExpressionAttributeValues', 'ExclusiveStartKey'])) {
                $value = $this->marshaler->marshalItem($value);
            }

            $serializedArgs[$arg] = $value;
        }

        return $serializedArgs;
    }
}
