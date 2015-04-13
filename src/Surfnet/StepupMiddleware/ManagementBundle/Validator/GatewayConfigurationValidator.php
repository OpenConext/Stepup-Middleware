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

class GatewayConfigurationValidator implements ConfigurationValidatorInterface
{
    private $serviceProviderConfigurationValidator;

    public function __construct(
        ServiceProviderConfigurationValidator $serviceProviderConfigurationValidator
    ) {
        $this->serviceProviderConfigurationValidator = $serviceProviderConfigurationValidator;
    }

    /**
     * @param array  $gatewayConfiguration
     * @param string $propertyPath
     */
    public function validate(array $gatewayConfiguration, $propertyPath)
    {
        StepupAssert::noExtraKeys(
            $gatewayConfiguration,
            ['service_providers'],
            "Expected only property 'service_providers'",
            $propertyPath
        );

        Assert::keyExists($gatewayConfiguration, 'service_providers', 'missing key service_providers', $propertyPath);
        $this->validateServiceProviders($gatewayConfiguration['service_providers'], $propertyPath . '.service_providers');
    }

    private function validateServiceProviders($serviceProviders, $propertyPath)
    {
        Assert::isArray(
            $serviceProviders,
            'service_providers must have an array of service provider configurations as value',
            'gateway.service_providers'
        );
        Assert::true(
            count($serviceProviders) >= 1,
            'at least one service_provider must be configured',
            'gateway.service_providers'
        );

        foreach ($serviceProviders as $index => $serviceProvider) {
            $this->serviceProviderConfigurationValidator->validate($serviceProvider, $propertyPath . '[' . $index. ']');
        }
    }
}
