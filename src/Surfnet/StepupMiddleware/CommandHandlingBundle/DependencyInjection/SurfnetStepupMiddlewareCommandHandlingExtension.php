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

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SurfnetStepupMiddlewareCommandHandlingExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('command_handlers.yml');
        $loader->load('event_sourcing.yml');
        $loader->load('pipeline.yml');
        $loader->load('processors.yml');

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), $config);

        $container
            ->getDefinition('surfnet_stepup_middleware_command_handling.email_sender')
            ->replaceArgument(0, $config['email_sender']['name'])
            ->replaceArgument(1, $config['email_sender']['email']);

        $container
            ->getDefinition('surfnet_stepup_middleware_command_handling.service.email_verification_mail')
            ->replaceArgument(4, $config['self_service_email_verification_url_template'])
            ->replaceArgument(6, $config['email_fallback_locale']);

        $container
            ->getDefinition('surfnet_stepup_middleware_command_handling.service.registration_mail')
            ->replaceArgument(5, $config['email_fallback_locale']);

        $container
            ->getDefinition('surfnet_stepup_middleware_command_handling.service.second_factor_revocation_mail')
            ->replaceArgument(5, $config['email_fallback_locale'])
            ->replaceArgument(6, $config['self_service_url']);

        $container
            ->getDefinition('surfnet_stepup_middleware_command_handling.service.registration_mail')
            ->replaceArgument(5, $config['email_fallback_locale']);
    }
}
