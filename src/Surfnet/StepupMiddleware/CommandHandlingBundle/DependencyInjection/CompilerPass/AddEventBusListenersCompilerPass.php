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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\DependencyInjection\CompilerPass;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddEventBusListenersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('surfnet_stepup_middleware_command_handling.event_bus.buffered');
        $eventListenerDefinitions = $container->findTaggedServiceIds('event_bus.event_listener');

        // When replaying events, certain listeners should not be allowed to run again, for instance
        // when they are no longer relevant at the time of replaying (i.e. sending emails)
        if (!in_array($container->getParameter('kernel.environment'), ['dev_event_replay', 'prod_event_replay', 'smoketest_event_replay'])) {
            foreach (array_keys($eventListenerDefinitions) as $serviceId) {
                $definition->addMethodCall('subscribe', [new Reference($serviceId)]);
            }

            return;
        }

        foreach ($eventListenerDefinitions as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['disable_for_replay'])) {
                    throw new LogicException(sprintf(
                        'Cannot replay events: Expected option "disable_for_replay" to be set for service id "%s"',
                        $serviceId
                    ));
                }

                if ($attributes['disable_for_replay']) {
                    continue;
                }

                $definition->addMethodCall('subscribe', [new Reference($serviceId)]);
            }
        }
    }
}
