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
    /**
     * @var \Surfnet\StepupMiddleware\ManagementBundle\Validator\IdentityProviderConfigurationValidator
     */
    private $identityProviderConfigurationValidator;

    /**
     * @var \Surfnet\StepupMiddleware\ManagementBundle\Validator\ServiceProviderConfigurationValidator
     */
    private $serviceProviderConfigurationValidator;

    public function __construct(
        IdentityProviderConfigurationValidator $identityProviderConfigurationValidator,
        ServiceProviderConfigurationValidator $serviceProviderConfigurationValidator
    ) {
        $this->identityProviderConfigurationValidator = $identityProviderConfigurationValidator;
        $this->serviceProviderConfigurationValidator = $serviceProviderConfigurationValidator;
    }

    /**
     * @param array  $gatewayConfiguration
     * @param string $propertyPath
     */
    public function validate(array $gatewayConfiguration, $propertyPath)
    {
        StepupAssert::keysMatch(
            $gatewayConfiguration,
            ['service_providers', 'identity_providers'],
            "Expected properties 'service_providers' and 'identity_providers'",
            $propertyPath
        );

        $this->validateIdentityProviders(
            $gatewayConfiguration['identity_providers'],
            $propertyPath . '.identity_providers'
        );
        $this->validateServiceProviders(
            $gatewayConfiguration['service_providers'],
            $propertyPath . '.service_providers'
        );
    }

    private function validateIdentityProviders($identityProviders, $propertyPath)
    {
        Assert::isArray(
            $identityProviders,
            'identity_providers must have an array of identity provider configurations as value',
            $propertyPath
        );

        foreach ($identityProviders as $index => $identityProvider) {
            $path = $propertyPath . '[' . $index . ']';
            Assert::isArray($identityProvider, 'Identity provider must be an object', $path);

            $this->identityProviderConfigurationValidator->validate($identityProvider, $path);
        }
    }

    private function validateServiceProviders($serviceProviders, $propertyPath)
    {
        Assert::isArray(
            $serviceProviders,
            'service_providers must have an array of service provider configurations as value',
            $propertyPath
        );
        Assert::true(
            count($serviceProviders) >= 1,
            'at least one service_provider must be configured',
            $propertyPath
        );

        foreach ($serviceProviders as $index => $serviceProvider) {
            $path = $propertyPath . '[' . $index . ']';
            Assert::isArray($serviceProvider, 'Service provider must be an object', $path);

            $this->serviceProviderConfigurationValidator->validate($serviceProvider, $path);
        }
    }
}
