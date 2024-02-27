<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddleware\MiddlewareBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('surfnet_stepup_middleware_middleware');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('second_factors_display_name')->isRequired()->scalarPrototype()->end()->end()
                ->scalarNode('email_verification_window')
                    ->info('The amount of seconds after which the email verification url/code expires')
                    ->defaultValue(3600)
                    ->validate()
                        ->ifTrue(function ($seconds): bool {
                            return !is_int($seconds) || $seconds < 1;
                        })
                        ->thenInvalid(
                            'The email verification window must be a positive integer'
                        )
                    ->end()
                ->end()
                ->arrayNode('enabled_generic_second_factors')
                    ->isRequired()
                    ->prototype('array')
                    ->children()
                        ->scalarNode('loa')
                            ->isRequired()
                            ->info('The lao level of the Gssf')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
