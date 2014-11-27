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

/**
 * Once the Assert 2.0 library has been built this should be converted to the lazy assertions so we can report
 * all errors at once.
 */
class ConfigurationValidator implements ValidatorInterface
{
    private $gatewayConfigurationValidator;

    public function __construct(
        GatewayConfigurationValidator $gatewayConfigurationValidator
    ) {
        $this->gatewayConfigurationValidator = $gatewayConfigurationValidator;
    }

    public function validate(array $configuration, $propertyPath)
    {
        Assert::isArray($configuration, 'Invalid body structure, must be an object', $propertyPath);
        Assert::keyExists($configuration, 'gateway', "Required property 'gateway' is missing", $propertyPath);
        $this->validateGatewayConfiguration($configuration, 'gateway');
    }

    private function validateGatewayConfiguration($configuration, $propertyPath)
    {
        Assert::isArray($configuration['gateway'], 'Property "gateway" should have an object as value', $propertyPath);

        $this->gatewayConfigurationValidator->validate($configuration['gateway'], $propertyPath);
    }
}
