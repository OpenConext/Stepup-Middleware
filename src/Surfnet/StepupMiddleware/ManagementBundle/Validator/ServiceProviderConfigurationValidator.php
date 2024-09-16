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

class ServiceProviderConfigurationValidator implements ConfigurationValidatorInterface
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function validate(array $configuration, string $propertyPath): void
    {
        $requiredProperties = [
            'entity_id',
            'public_key',
            'acs',
            'loa',
            'assertion_encryption_enabled',
            'second_factor_only',
            'second_factor_only_nameid_patterns',
            'blacklisted_encryption_algorithms',
            'use_pdp',
            'allow_sso_on_2fa',
            'set_sso_cookie_on_2fa',
        ];

        if (empty($configuration['use_pdp'])) {
            $configuration['use_pdp'] = false;
        }

        if (empty($configuration['allow_sso_on_2fa'])) {
            $configuration['allow_sso_on_2fa'] = false;
        }

        if (empty($configuration['set_sso_cookie_on_2fa'])) {
            $configuration['set_sso_cookie_on_2fa'] = false;
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
        $this->validateStringValue($configuration, 'public_key', $propertyPath);
        $this->validateAssertionConsumerUrls($configuration, $propertyPath);
        $this->validateLoaDefinition($configuration, $propertyPath);
        $this->validateBooleanValue(
            $configuration,
            'assertion_encryption_enabled',
            $propertyPath,
        );
        $this->validateBooleanValue(
            $configuration,
            'second_factor_only',
            $propertyPath,
        );
        $this->validateListOfNameIdPatterns(
            $configuration,
            'second_factor_only_nameid_patterns',
            $propertyPath,
        );
        $this->validateStringValues(
            $configuration,
            'blacklisted_encryption_algorithms',
            $propertyPath,
        );
        $this->validateBooleanValue($configuration, 'use_pdp', $propertyPath);
        $this->validateBooleanValue($configuration, 'allow_sso_on_2fa', $propertyPath);
        $this->validateBooleanValue($configuration, 'set_sso_cookie_on_2fa', $propertyPath);
    }

    private function validateStringValue(array $configuration, string $name, string $propertyPath): void
    {
        Assertion::string($configuration[$name], 'value must be a string', $propertyPath . '.' . $name);
    }

    private function validateStringValues(array $configuration, string $name, string $propertyPath): void
    {
        Assertion::isArray($configuration[$name], 'value must be an array', $propertyPath . '.' . $name);
        Assertion::allString($configuration[$name], 'value must be an array of strings', $propertyPath . '.' . $name);
    }

    private function validateBooleanValue(array $configuration, string $name, string $propertyPath): void
    {
        Assertion::boolean($configuration[$name], 'value must be a boolean', $propertyPath . '.' . $name);
    }

    private function validateAssertionConsumerUrls(array $configuration, string $propertyPath): void
    {
        $value = $configuration['acs'];
        $propertyPath .= '.acs';

        Assertion::isArray($value, 'must contain a non-empty array of strings', $propertyPath);
        Assertion::true(count($value) >= 1, 'array must contain at least one value', $propertyPath);
        Assertion::allString($value, 'must be an array of strings', $propertyPath);
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

        // Test if all SP specific LoA configuration entries are lower case.
        $this->assertValidInstitutionIdentifiers(
            $value,
            'The shacHomeOrganisation names in SP LoA configuration must all be lower case',
            $path,
        );
    }

    private function validateListOfNameIdPatterns(array $configuration, string $name, string $propertyPath): void
    {
        $value = $configuration[$name];
        $propertyPath = $propertyPath . '.' . $name;

        Assertion::isArray($value, 'must contain an array', $propertyPath);
        Assertion::allString($value, 'must be an array of strings', $propertyPath);
    }

    /**
     * All institution names (SHO values) should be lowercase.
     *
     * For example:
     *  [
     *     '__default__'      => 'loa1', // valid
     *     'institution-1.nl' => 'loa1', // valid
     *     'My.Institution'   => 'loa2', // invalid
     *  ]
     *
     */
    private function assertValidInstitutionIdentifiers(
        array $spLoaConfiguration,
        string $message,
        string $propertyPath,
    ): void {
        $assertLowerCase = fn($sho): bool => $sho === strtolower((string)$sho);

        // The array keys match the institution name / SHO.
        $lowerCaseTestResults = array_map($assertLowerCase, array_keys($spLoaConfiguration));
        Assertion::allTrue($lowerCaseTestResults, $message, $propertyPath);
    }
}
