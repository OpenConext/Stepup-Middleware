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
        Assert::keyExists($gatewayConfiguration, 'service_provider', 'missing key service_provider', $propertyPath);
        $this->validateServiceProviders($gatewayConfiguration['service_provider'], $propertyPath . '.service_provider');
    }

    private function validateServiceProviders($serviceProviders, $propertyPath)
    {
        Assert::isArray(
            $serviceProviders,
            'service_provider must have an array of service provider configurations as value',
            'gateway.service_provider'
        );
        Assert::true(
            count($serviceProviders) >= 1,
            'at least one service_provider must be configured',
            'gateway.service_provider'
        );

        foreach ($serviceProviders as $index => $serviceProvider) {
            $this->serviceProviderConfigurationValidator->validate($serviceProvider, $propertyPath . '[' . $index. ']');
        }
    }
}
