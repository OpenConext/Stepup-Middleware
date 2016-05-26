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

use Assert\Assertion as Assert;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;

class ServiceProviderConfigurationValidator implements ConfigurationValidatorInterface
{
    public function validate(array $configuration, $propertyPath)
    {
        Assert::isArray($configuration, 'invalid configuration format, must be an object', $propertyPath);

        $acceptedProperties = [
            'entity_id',
            'public_key',
            'acs',
            'loa',
            'assertion_encryption_enabled',
            'second_factor_only',
            'second_factor_only_nameid_patterns',
            'blacklisted_encryption_algorithms',
        ];
        StepupAssert::keysMatch(
            $configuration,
            $acceptedProperties,
            sprintf(
                "The following properties must be present: '%s'; other properties are not supported",
                join("', '", $acceptedProperties)
            ),
            $propertyPath
        );

        $this->validateStringValue($configuration, 'entity_id', $propertyPath);
        $this->validateStringValue($configuration, 'public_key', $propertyPath);
        $this->validateAssertionConsumerUrls($configuration, $propertyPath);
        $this->validateLoaDefinition($configuration, $propertyPath);
        $this->validateBooleanValue(
            $configuration,
            'assertion_encryption_enabled',
            $propertyPath
        );
        $this->validateBooleanValue(
          $configuration,
          'second_factor_only',
          $propertyPath
        );
        $this->validateListOfNameIdPatterns(
          $configuration,
          'second_factor_only_nameid_patterns',
          $propertyPath
        );
        $this->validateStringValues(
            $configuration,
            'blacklisted_encryption_algorithms',
            $propertyPath
        );
    }

    /**
     * @param array  $configuration
     * @param string $name
     * @param string $propertyPath
     */
    private function validateStringValue($configuration, $name, $propertyPath)
    {
        Assert::string($configuration[$name], 'value must be a string', $propertyPath . '.' . $name);
    }

    /**
     * @param array  $configuration
     * @param string $name
     * @param string $propertyPath
     */
    private function validateStringValues($configuration, $name, $propertyPath)
    {
        Assert::isArray($configuration[$name], 'value must be an array', $propertyPath . '.' . $name);
        Assert::allString($configuration[$name], 'value must be an array of strings', $propertyPath . '.' . $name);
    }

    /**
     * @param array  $configuration
     * @param string $name
     * @param string $propertyPath
     */
    private function validateBooleanValue($configuration, $name, $propertyPath)
    {
        Assert::boolean($configuration[$name], 'value must be a boolean', $propertyPath . '.' . $name);
    }

    /**
     * @param array  $configuration
     * @param string $propertyPath
     */
    private function validateAssertionConsumerUrls($configuration, $propertyPath)
    {
        $value = $configuration['acs'];
        $propertyPath = $propertyPath . '.acs';

        Assert::isArray($value, 'must contain a non-empty array of strings', $propertyPath);
        Assert::true(count($value) >= 1, 'array must contain at least one value', $propertyPath);
        Assert::allString($value, 'must be an array of strings', $propertyPath);
    }

    /**
     * @param array  $configuration
     * @param string $propertyPath
     */
    private function validateLoaDefinition($configuration, $propertyPath)
    {
        $value = $configuration['loa'];
        $path  = $propertyPath . '.loa';

        Assert::isArray($value, 'must be an object', $path);
        Assert::keyExists($value, '__default__', "must have the default loa set on the '__default__' property", $path);
        Assert::allString($value, 'all properties must contain strings as values', $path);
    }

    /**
     * @param array $configuration
     * @param string $name
     * @param string $propertyPath
     */
    private function validateListOfNameIdPatterns($configuration, $name, $propertyPath)
    {
        $value = $configuration[$name];
        $propertyPath = $propertyPath . '.' . $name;

        Assert::isArray($value, 'must contain an array', $propertyPath);
        Assert::allString($value, 'must be an array of strings', $propertyPath);
    }
}
