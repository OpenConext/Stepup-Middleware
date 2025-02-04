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

use Surfnet\StepupMiddleware\ApiBundle\Exception\BadCommandRequestException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CommandValueResolver implements ValueResolverInterface
{
    /**
     * @return AbstractCommand[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (!$argumentType
            || (!is_subclass_of($argumentType, Command::class, true) && Command::class !== $argumentType)
        ) {
            return [];
        }

        $data = json_decode($request->getContent(), true);

        $this->assertIsValidCommandStructure($data);

        $commandNameParts = [];
        $found = preg_match('~^(\w+):([\w\\.]+)$~', (string)$data['command']['name'], $commandNameParts);
        if ($found !== 1) {
            $message = sprintf('Command does not have a valid command name %s', (string)$data['command']['name']);
            throw new BadCommandRequestException(
                [$message],
                $message,
            );
        }
        $commandClassName = sprintf(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\%s\Command\%sCommand',
            $commandNameParts[1],
            str_replace('.', '\\', $commandNameParts[2]),
        );

        /** @var AbstractCommand $command */
        $command = new $commandClassName;
        $command->UUID = (string)$data['command']['uuid'];

        foreach ((array)$data['command']['payload'] as $property => $value) {
            $properlyCasedProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$property))));
            $command->$properlyCasedProperty = $value;
        }

        return [$command];
    }

    /**
     * @throws BadCommandRequestException
     */
    private function assertIsValidCommandStructure(mixed $data): void
    {
        if (!is_array($data)) {
            $type = gettype($data);

            throw new BadCommandRequestException(
                [sprintf('Command is not valid: body must be a JSON object, but is of type %s', $type)],
            );
        }

        if (!isset($data['command'])) {
            throw new BadCommandRequestException(["Required parameter 'command' is not set."]);
        }

        if (!isset($data['command']['name']) || !is_string($data['command']['name'])) {
            throw new BadCommandRequestException(["Required command parameter 'name' is not set or not a string."]);
        }

        if (!isset($data['command']['uuid']) || !is_string($data['command']['uuid'])) {
            throw new BadCommandRequestException(["Required command parameter 'uuid' is not set or not a string."]);
        }

        if (!isset($data['command']['payload']) || !is_array($data['command']['payload'])) {
            throw new BadCommandRequestException(["Required command parameter 'payload' is not set or not an object."]);
        }
    }
}
