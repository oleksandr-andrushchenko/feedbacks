<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use OA\Dynamodb\Metadata\MetadataLoader;
use OA\Dynamodb\Serializer\EntityDenormalizer;
use OA\Dynamodb\Serializer\EntityNormalizer;
use OA\Dynamodb\Serializer\EntitySerializer;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

readonly class EntityManagerFactory
{
    /**
     * @param array<string, mixed> $dbClientOptions
     * @param array<string, mixed> $options
     */
    public static function create(array $dbClientOptions, array $options = []): EntityManager
    {
        $datetimeFormat = $options[EntityNormalizer::DATETIME_FORMAT_KEY] ?? 'Y-m-d\TH:i:s.u\Z';

        $dynamoDbClient = new DynamoDbClient($dbClientOptions);
        $metadataLoader = new MetadataLoader();
        $serializer = new Serializer([
            new BackedEnumNormalizer(),
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => $datetimeFormat]),
            new ObjectNormalizer(
                propertyTypeExtractor: new PropertyInfoExtractor(typeExtractors: [new ReflectionExtractor()]),
            ),
        ]);

        $marshaler = new Marshaler();
        $normalizer = new EntityNormalizer($metadataLoader, $serializer);
        $denormalizer = new EntityDenormalizer($metadataLoader, $serializer);
        $entitySerializer = new EntitySerializer($normalizer, $denormalizer, $marshaler);
        $opArgsBuilder = new OpArgsBuilder($serializer, $marshaler);

        return new EntityManager(
            $dynamoDbClient,
            $metadataLoader,
            $entitySerializer,
            $opArgsBuilder,
            $options['table'] ?? null
        );
    }
}
