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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

class AddPipelineStagesCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc} Since the priorities cannot be changed runtime but only through configuration, we're doing the
     * sorting based on priority here. A higher priority means the stage is added earlier.
     */
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('surfnet_stepup_middleware_command_handling.pipeline.staged_pipeline');
        $stageDefinitions = $container->findTaggedServiceIds('pipeline.stage');

        $prioritized = [];
        foreach ($stageDefinitions as $stageServiceId => $tagAttributes) {
            $priority = $tagAttributes[0]['priority'];
            if (isset($prioritized[$priority])) {
                throw new InvalidConfigurationException(sprintf(
                    'Cannot add stage with service_id "%s" to StagedPipeline at priority "%d", Stage with service_id '
                    . '"%s" is already registered at that position',
                    $stageServiceId,
                    $tagAttributes['priority'],
                    (string) $prioritized[$priority]
                ));
            }

            $prioritized[$priority] = new Reference($stageServiceId);
        }

        if (!ksort($prioritized)) {
            throw new RuntimeException('Could not sort stages based on prioritization (ksort failed)');
        }

        // ksort sorts low -> high, so reversing to get them sorted correctly.
        $prioritized = array_reverse($prioritized);

        foreach ($prioritized as $reference) {
            $definition->addMethodCall('addStage', [$reference]);
        }
    }
}
