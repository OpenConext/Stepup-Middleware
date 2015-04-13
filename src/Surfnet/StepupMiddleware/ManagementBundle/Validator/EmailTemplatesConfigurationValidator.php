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
use Surfnet\StepupMiddleware\ManagementBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;

final class EmailTemplatesConfigurationValidator implements ConfigurationValidatorInterface
{
    /**
     * @var string
     */
    private $requiredLocale;

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

    public function validate(array $configuration, $propertyPath)
    {
        $templateNames = ['confirm_email', 'registration_code'];

        StepupAssert::noExtraKeys(
            $configuration,
            $templateNames,
            sprintf("Expected only templates '%s'", join(',', $templateNames)),
            $propertyPath
        );

        foreach ($templateNames as $templateName) {
            Assert::keyExists(
                $configuration,
                $templateName,
                "Required property '" . $templateName . "' is missing",
                $propertyPath
            );
            Assert::isArray(
                $configuration[$templateName],
                'Property "' . $templateName . '" must have an object as value',
                $propertyPath
            );

            $templatePropertyPath = $propertyPath . '.' . $templateName;

            Assert::keyExists(
                $configuration[$templateName],
                $this->requiredLocale,
                "Required property '" . $this->requiredLocale . "' is missing",
                $templatePropertyPath
            );
            Assert::string(
                $configuration[$templateName][$this->requiredLocale],
                "Property '" . $this->requiredLocale . "' must have a string as value",
                $templatePropertyPath
            );
        }
    }
}
