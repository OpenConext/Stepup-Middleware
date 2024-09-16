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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('surfnet_stepup_middleware_command_handling');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('self_service_email_verification_url_template')
                    ->isRequired()
                    ->info('Configures the URL where registrants can verify e-mail address ownership.')
                    ->validate()
                        ->ifTrue(function ($url): bool {
                            $parts = parse_url($url);

                            return empty($parts['scheme']) || empty($parts['host']) || empty($parts['path']);
                        })
                        ->thenInvalid(
                            'Invalid Self-Service e-mail verification URL template: ' .
                            "must be full Self-Service URL with scheme, host and path, '%s' given." .
                            "The URL should contain a '{identityId}', '{secondFactorId}' and '{nonce}' parameter."
                        )
                    ->end()
                ->end()
                ->scalarNode('self_service_url')
                    ->isRequired()
                    ->info('Configures the URL for Self Service.')
                    ->validate()
                        ->ifTrue(
                            function ($url): bool {
                                return filter_var($url, FILTER_VALIDATE_URL) === false;
                            }
                        )
                        ->thenInvalid('self_service_url must be a valid url')
                    ->end()
                ->end()
                ->arrayNode('email_sender')
                    ->isRequired()
                    ->info('Configures the sender used for all outgoing e-mail messages')
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(function ($name): bool {
                                    return !is_string($name) || empty($name);
                                })
                                ->thenInvalid("E-mail sender name must be non-empty string, got '%s'")
                            ->end()
                        ->end()
                        ->scalarNode('email')
                            ->isRequired()
                            ->validate()
                                ->ifTrue(function ($name): bool {
                                    return !is_string($name) || empty($name);
                                })
                                ->thenInvalid("E-mail sender e-mail must be non-empty string, got '%s'")
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('email_fallback_locale')->isRequired()->end()
            ->end();

        return $treeBuilder;
    }
}
