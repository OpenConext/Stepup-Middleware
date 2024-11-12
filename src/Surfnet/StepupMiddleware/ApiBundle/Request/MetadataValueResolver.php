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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MetadataValueResolver implements ValueResolverInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @return Metadata[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (!$argumentType
            || $argumentType !== Metadata::class
        ) {
            return [];
        }

        $data = json_decode($request->getContent());

        $this->assertIsValidMetadataStructure($data);

        $metadata = new Metadata();

        foreach ((array)$data->meta as $property => $value) {
            $properlyCasedProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$property))));
            $metadata->$properlyCasedProperty = $value;
        }

        $violations = $this->validator->validate($metadata);
        if (count($violations) > 0) {
            throw BadCommandRequestException::withViolations('Command metadata is not valid', $violations);
        }

        return [$metadata];
    }

    /**
     * @throws BadCommandRequestException
     */
    private function assertIsValidMetadataStructure(mixed $data): void
    {
        if (!is_object($data)) {
            $type = gettype($data);

            throw new BadCommandRequestException(
                [sprintf('Command metadata is not valid: body must be a JSON object, but is of type %s', $type)],
            );
        }

        if (!isset($data->meta)) {
            throw new BadCommandRequestException(["Required parameter 'meta' is not set."]);
        }

        if (!is_object($data->meta)) {
            $type = gettype($data);

            throw new BadCommandRequestException([
                sprintf(
                    "Command metadata is not valid: 'meta' key value must be a JSON object, but is of type %s",
                    $type,
                ),
            ]);
        }
    }
}
