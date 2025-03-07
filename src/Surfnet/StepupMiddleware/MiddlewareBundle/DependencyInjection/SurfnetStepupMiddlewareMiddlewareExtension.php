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

use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SurfnetStepupMiddlewareMiddlewareExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), $config);

        $fileLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $fileLoader->load('services.yml');
        $fileLoader->load('console_commands.yml');
        $fileLoader->load('event_replaying.yml');

        $definition = (new Definition())
            ->setClass(ConfigurableSettings::class)
            ->setFactory('Surfnet\Stepup\Identity\Entity\ConfigurableSettings::create')
            ->setArguments([$config['email_verification_window'], $container->getParameter('locales')]);

        $container->setDefinition('identity.entity.configurable_settings', $definition);

        $container->setParameter(
            'middleware.enabled_generic_second_factors',
            $config['enabled_generic_second_factors'],
        );
    }
}
