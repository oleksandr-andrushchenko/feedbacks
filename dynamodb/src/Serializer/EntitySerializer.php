<?php

declare(strict_types=1);

namespace OA\Dynamodb\Serializer;

use Aws\DynamoDb\Marshaler;
use OA\Dynamodb\Metadata\MetadataException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntitySerializer
{
    public function __construct(
        protected EntityNormalizer $entityNormalizer,
        protected EntityDenormalizer $entityDenormalizer,
        protected Marshaler $marshaler,
        protected ?LoggerInterface $logger = null,
    )
    {
    }

    public function getEntityNormalizer(): EntityNormalizer
    {
        return $this->entityNormalizer;
    }

    /**
     * @template T of object
     * @param T $entity
     * @return array<string, array<mixed, mixed>>
     * @throws ExceptionInterface
     * @throws MetadataException
     * @throws ReflectionException
     */
    public function serialize(object $entity, bool $includePrimaryKey = true): array
    {
        $normalizedEntity = $this->entityNormalizer->normalize($entity, $includePrimaryKey);

//        $this->logger?->debug(__METHOD__, ['entity' => $normalizedEntity]);

        return $this->marshaler->marshalItem($normalizedEntity);
    }

    /**
     * @template T of object
     * @param T|class-string<T> $entity
     * @param array<string, mixed> $keyFieldValues
     * @return array<string, string>
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    public function serializePrimaryKey(object|string $entity, array $keyFieldValues = []): array
    {
        $normalizedEntity = $this->entityNormalizer->normalizePrimaryKey($entity, $keyFieldValues);

        return $this->marshaler->marshalItem($normalizedEntity);
    }

    /**
     * @template T of object
     * @param array<string, array<mixed, mixed>> $data
     * @param class-string<T> $class
     * @return T
     * @throws ExceptionInterface
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function deserialize(array $data, string $class): object
    {
        /** @var array<string, mixed> $normalizedData */
        $normalizedData = $this->marshaler->unmarshalItem($data);

        return $this->entityDenormalizer->denormalize($normalizedData, $class);
    }
}
