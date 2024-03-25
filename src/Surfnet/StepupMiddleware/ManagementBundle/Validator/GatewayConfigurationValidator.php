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
use Assert\AssertionFailedException;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Assert as StepupAssert;

class GatewayConfigurationValidator implements ConfigurationValidatorInterface
{
    public function __construct(
        private readonly IdentityProviderConfigurationValidator $identityProviderConfigurationValidator,
        private readonly ServiceProviderConfigurationValidator $serviceProviderConfigurationValidator,
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function validate(array $configuration, string $propertyPath): void
    {
        StepupAssert::keysMatch(
            $configuration,
            ['service_providers', 'identity_providers'],
            "Expected properties 'service_providers' and 'identity_providers'",
            $propertyPath,
        );

        $this->validateIdentityProviders(
            $configuration['identity_providers'],
            $propertyPath . '.identity_providers',
        );
        $this->validateServiceProviders(
            $configuration['service_providers'],
            $propertyPath . '.service_providers',
        );
    }

    /**
     * @param array<string, array<string, mixed>> $identityProviders
     * @throws AssertionFailedException
     */
    private function validateIdentityProviders(array $identityProviders, string $propertyPath): void
    {
        foreach ($identityProviders as $index => $identityProvider) {
            $path = $propertyPath . '[' . $index . ']';
            Assertion::isArray($identityProvider, 'Identity provider must be an object', $path);

            $this->identityProviderConfigurationValidator->validate($identityProvider, $path);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $serviceProviders
     * @throws AssertionFailedException
     */
    private function validateServiceProviders(array $serviceProviders, string $propertyPath): void
    {
        Assertion::true(
            count($serviceProviders) >= 1,
            'at least one service_provider must be configured',
            $propertyPath,
        );

        foreach ($serviceProviders as $index => $serviceProvider) {
            $path = $propertyPath . '[' . $index . ']';
            Assertion::isArray($serviceProvider, 'Service provider must be an object', $path);

            $this->serviceProviderConfigurationValidator->validate($serviceProvider, $path);
        }
    }
}
