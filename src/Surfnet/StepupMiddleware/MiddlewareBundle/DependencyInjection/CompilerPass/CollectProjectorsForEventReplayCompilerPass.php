<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CollectProjectorsForEventReplayCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $projectorCollectionDefinition = $container->getDefinition('middleware.event_replay.projector_collection');
        $projectorDefinitions = $container->findTaggedServiceIds('projector.register_for_replay');

        foreach (array_keys($projectorDefinitions) as $serviceId) {
            $projectorCollectionDefinition->addMethodCall('add', [new Reference($serviceId)]);
        }
    }
}
