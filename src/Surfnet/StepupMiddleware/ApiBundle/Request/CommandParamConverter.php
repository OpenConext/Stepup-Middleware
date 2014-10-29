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

namespace Surfnet\StepupMiddleware\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Symfony\Component\HttpFoundation\Request;

class CommandParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        $object = json_decode($request->getContent(), true);

        $this->assertIsValidCommandStructure($object);

        preg_match('~^(\w+):([\w\\.]+)$~', $object['command']['name'], $commandName);
        $commandClassName = sprintf(
            'Surfnet\Stepup\%s\Command\%sCommand',
            $commandName[1],
            str_replace('.', '\\', $commandName[2])
        );

        $command = new $commandClassName;
        $command->UUID = $object['command']['uuid'];

        foreach ($object['command']['payload'] as $property => $value) {
            $properlyCasedProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
            $command->$properlyCasedProperty = $value;
        }

        $request->attributes->set('command', $command);
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getName() === 'command'
            && $configuration->getClass() === 'Surfnet\Stepup\Command\Command';
    }

    /**
     * @param $object
     * @throws BadCommandRequestException
     */
    private function assertIsValidCommandStructure($object)
    {
        if (!is_array($object)) {
            throw new BadCommandRequestException(['Command is not valid: body must be a JSON object.']);
        }

        if (!isset($object['command'])) {
            throw new BadCommandRequestException(['Command is not valid: no command object.']);
        }

        if (!isset($object['command']['name']) || !is_string($object['command']['name'])) {
            throw new BadCommandRequestException(['Command is not valid: pass command name string.']);
        }

        if (!isset($object['command']['uuid']) || !is_string($object['command']['uuid'])) {
            throw new BadCommandRequestException(['Command is not valid: pass UUID.']);
        }

        if (!isset($object['command']['payload']) || !is_array($object['command']['payload'])) {
            throw new BadCommandRequestException(['Command is not valid: pass payload object.']);
        }
    }
}
