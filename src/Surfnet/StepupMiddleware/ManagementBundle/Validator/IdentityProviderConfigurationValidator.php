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

namespace Surfnet\StepupMiddleware\ManagementBundle\Validator;

use Assert\Assertion;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;

class IdentityProviderConfigurationValidator implements ConfigurationValidatorInterface
{
    public function validate(array $configuration, $propertyPath): void
    {
        Assertion::isArray($configuration, 'invalid configuration format, must be an object', $propertyPath);

        $requiredProperties = [
            'entity_id',
            'loa',
            'use_pdp',
        ];

        if (empty($configuration['use_pdp'])) {
            $configuration['use_pdp'] = false;
        }

        StepupAssert::keysMatch(
            $configuration,
            $requiredProperties,
            sprintf(
                "The following properties must be present: '%s'; other properties are not supported",
                implode("', '", $requiredProperties),
            ),
            $propertyPath,
        );

        $this->validateStringValue($configuration, 'entity_id', $propertyPath);
        $this->validateLoaDefinition($configuration, $propertyPath);
        $this->validateBooleanValue($configuration, 'use_pdp', $propertyPath);
    }

    private function validateStringValue(array $configuration, string $name, string $propertyPath): void
    {
        Assertion::string($configuration[$name], 'value must be a string', $propertyPath . '.' . $name);
    }

    private function validateBooleanValue(array $configuration, string $name, string $propertyPath): void
    {
        Assertion::boolean($configuration[$name], 'value must be a boolean', $propertyPath . '.' . $name);
    }

    private function validateLoaDefinition(array $configuration, string $propertyPath): void
    {
        $value = $configuration['loa'];
        $path = $propertyPath . '.loa';

        Assertion::isArray($value, 'must be an object', $path);
        Assertion::keyExists(
            $value,
            '__default__',
            "must have the default loa set on the '__default__' property",
            $path,
        );
        Assertion::allString($value, 'all properties must contain strings as values', $path);
    }
}
