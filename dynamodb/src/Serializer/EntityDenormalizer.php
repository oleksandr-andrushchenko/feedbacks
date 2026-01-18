<?php

declare(strict_types=1);

namespace OA\Dynamodb\Serializer;

use OA\Dynamodb\Metadata\MetadataException;
use OA\Dynamodb\Metadata\MetadataLoader;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class EntityDenormalizer
{
    public function __construct(
        protected MetadataLoader $metadataLoader,
        protected DenormalizerInterface $denormalizer,
        protected ?string $denormalizeFormat = 'dynamodb',
    ) {
    }

    /**
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $class
     * @return T
     * @throws ExceptionInterface
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function denormalize(array $data, string $class): object
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);
        $propertyAttributes = $entityMetadata->getPropertyAttributes();

        $normalizedData = [];

        foreach ($propertyAttributes as $prop => $attr) {
            $value = $data[$attr->getName() ?: $prop] ?? null;

            if (null !== $value) {
                $normalizedData[$prop] = $value;
            }
        }

        return $this->denormalizer->denormalize($normalizedData, $class, $this->denormalizeFormat);
    }
}
