<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Bundle\JoseFramework\DependencyInjection\Source\Encryption;

use Jose\Bundle\JoseFramework\DependencyInjection\Source\Source;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class AbstractSource.
 */
abstract class AbstractEncryptionSource implements Source
{
    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode($this->name())
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->booleanNode('is_public')
                                ->info('If true, the service will be public, else private.')
                                ->defaultTrue()
                            ->end()
                            ->arrayNode('key_encryption_algorithms')
                                ->info('A list of supported key encryption algorithms.')
                                ->useAttributeAsKey('name')
                                ->isRequired()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('content_encryption_algorithms')
                                ->info('A list of supported content encryption algorithms.')
                                ->useAttributeAsKey('name')
                                ->isRequired()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('compression_methods')
                                ->info('A list of supported compression methods.')
                                ->useAttributeAsKey('name')
                                ->defaultValue(['DEF'])
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
