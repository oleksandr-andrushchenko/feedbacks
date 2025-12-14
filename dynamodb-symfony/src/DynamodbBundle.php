<?php

declare(strict_types=1);

namespace OA\DynamodbBundle;

use OA\Dynamodb\Metadata\MetadataLoader;
use OA\Dynamodb\ODM\EntityManager;
use OA\Dynamodb\ODM\EntityRepository;
use OA\Dynamodb\ODM\OpArgsBuilder;
use OA\Dynamodb\Serializer\EntityDenormalizer;
use OA\Dynamodb\Serializer\EntityNormalizer;
use OA\Dynamodb\Serializer\EntitySerializer;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class DynamodbBundle extends AbstractBundle
{
    /**
     * @inheritDoc
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @phpstan-ignore-next-line */
        $definition->rootNode()
            ->children()
            ->scalarNode('client')->info('Service ID of the AWS DynamoDB client to use')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('marshaler')->info('Service ID of the AWS Marshaler to use')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('serializer')->info('Service ID of the Symfony Serializer to use')
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('logger')->info('Service ID of the Logger to use')
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('table')
            ->defaultValue(null)
            ->cannotBeEmpty()
            ->info('Default DynamoDB table name')
            ->end()
            ->end()
        ;
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $clientId = $config['client'];
        $marshalerId = $config['marshaler'];
        $serializerId = $config['serializer'];
        $loggerId = $config['logger'];
        $defaults = [
            'table' => $config['table'],
        ];

        $services = $container->services();

        $services->set('oa.dynamodb.metadata_loader', MetadataLoader::class)
            ->arg('$defaults', $defaults)
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.entity_normalizer', EntityNormalizer::class)
            ->arg('$metadataLoader', new ReferenceConfigurator('oa.dynamodb.metadata_loader'))
            ->arg('$normalizer', new ReferenceConfigurator($serializerId))
            ->arg('$logger', new ReferenceConfigurator($loggerId))
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.entity_denormalizer', EntityDenormalizer::class)
            ->arg('$metadataLoader', new ReferenceConfigurator('oa.dynamodb.metadata_loader'))
            ->arg('$denormalizer', new ReferenceConfigurator($serializerId))
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.entity_serializer', EntitySerializer::class)
            ->arg('$entityNormalizer', new ReferenceConfigurator('oa.dynamodb.entity_normalizer'))
            ->arg('$entityDenormalizer', new ReferenceConfigurator('oa.dynamodb.entity_denormalizer'))
            ->arg('$marshaler', new ReferenceConfigurator($marshalerId))
            ->arg('$logger', new ReferenceConfigurator($loggerId))
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.op_args_builder', OpArgsBuilder::class)
            ->arg('$normalizer', new ReferenceConfigurator($serializerId))
            ->arg('$marshaler', new ReferenceConfigurator($marshalerId))
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.entity_manager', EntityManager::class)
            ->arg('$dynamoDbClient', new ReferenceConfigurator($clientId))
            ->arg('$metadataLoader', new ReferenceConfigurator('oa.dynamodb.metadata_loader'))
            ->arg('$entitySerializer', new ReferenceConfigurator('oa.dynamodb.entity_serializer'))
            ->arg('$opArgsBuilder', new ReferenceConfigurator('oa.dynamodb.op_args_builder'))
            ->arg('$logger', new ReferenceConfigurator($loggerId))
            ->autowire()
            ->autoconfigure()
        ;

        $services->set('oa.dynamodb.entity_repository', EntityRepository::class)
            ->arg('$em', new ReferenceConfigurator('oa.dynamodb.entity_manager'))
            ->call('setLogger', [new ReferenceConfigurator($loggerId)])
            ->abstract()
            ->autowire()
            ->autoconfigure()
        ;
    }
}
