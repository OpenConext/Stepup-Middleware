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
use Surfnet\StepupMiddleware\ManagementBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;

final class EmailTemplatesConfigurationValidator implements ConfigurationValidatorInterface
{
    private readonly string $requiredLocale;

    /**
     * @param string $requiredLocale
     */
    public function __construct($requiredLocale)
    {
        if (!is_string($requiredLocale)) {
            throw InvalidArgumentException::invalidType('string', 'defaultLocale', $requiredLocale);
        }

        $this->requiredLocale = $requiredLocale;
    }

    public function validate(array $configuration, $propertyPath): void
    {
        $templateNames = [
            'confirm_email',
            'registration_code_with_ras',
            'registration_code_with_ra_locations',
            'vetted',
            'second_factor_revoked',
            'second_factor_verification_reminder_with_ras',
            'second_factor_verification_reminder_with_ra_locations',
            'recovery_token_created',
            'recovery_token_revoked',
        ];

        StepupAssert::keysMatch(
            $configuration,
            $templateNames,
            sprintf("Expected only templates '%s'", implode(',', $templateNames)),
            $propertyPath,
        );

        foreach ($templateNames as $templateName) {
            Assertion::isArray(
                $configuration[$templateName],
                'Property "' . $templateName . '" must have an object as value',
                $propertyPath,
            );

            $templatePropertyPath = $propertyPath . '.' . $templateName;

            Assertion::keyExists(
                $configuration[$templateName],
                $this->requiredLocale,
                "Required property '" . $this->requiredLocale . "' is missing",
                $templatePropertyPath,
            );

            foreach ($configuration[$templateName] as $locale => $template) {
                $localePropertyPath = $templatePropertyPath . '[' . $locale . ']';
                Assertion::string(
                    $locale,
                    'Locale must be string',
                    $localePropertyPath,
                );
                Assertion::string(
                    $template,
                    "Property '" . $this->requiredLocale . "' must have a string as value",
                    $localePropertyPath,
                );
            }
        }
    }
}
