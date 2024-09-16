<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\Service;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;

class InstitutionConfigurationOptionsServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private InstitutionConfigurationOptionsService $service;

    private InstitutionConfigurationOptionsRepository&MockInterface $repository;

    /**
     * A representation of the globally configured application setting for the numberOfTokensPerIdentity, this value
     * is configured in the parameters.yml under the moniker of 'number_of_tokens_per_identity'
     */
    private int $numberOfTokensPerIdentityDefault = 13;

    public function setUp(): void
    {
        $this->repository = m::mock(InstitutionConfigurationOptionsRepository::class);

        $this->service = new InstitutionConfigurationOptionsService(
            $this->repository,
            $this->numberOfTokensPerIdentityDefault,
        );
    }

    public function test_get_max_number_of_tokens_for_with_available_institution_configuration(): void
    {
        $institution = new Institution('surfnet.nl');

        $expectedNumberOfTokens = 4;
        $expectedConfigurationOptions = $this->buildConfigurationOption($expectedNumberOfTokens);

        $this->repository
            ->shouldReceive('findConfigurationOptionsFor')
            ->andReturn($expectedConfigurationOptions);

        $numberOfTokens = $this->service->getMaxNumberOfTokensFor($institution);

        $this->assertEquals($expectedNumberOfTokens, $numberOfTokens);
    }

    /**
     * Simulates the use case where an institution does have a specific institution config, but the token setting is
     * disabled.
     */
    public function test_get_max_number_of_tokens_for_with_default_institution_configuration_settings(): void
    {
        $institution = new Institution('surfnet.nl');

        $expectedConfigurationOptions = $this->buildConfigurationOption(0);

        $this->repository
            ->shouldReceive('findConfigurationOptionsFor')
            ->andReturn($expectedConfigurationOptions);

        $numberOfTokens = $this->service->getMaxNumberOfTokensFor($institution);

        // One is configured as the globally set application default
        $expectedNumberOfTokens = 13;
        $this->assertEquals($expectedNumberOfTokens, $numberOfTokens);
    }


    /**
     * Simulates the use case where an institution does not have specific institution config, but defaults are used
     * instead.
     */
    public function test_nullable_tokens_per_identity_options_in_institution_configuration_settings(): void
    {
        $institution = new Institution('surfnet.nl');

        $this->repository
            ->shouldReceive('findConfigurationOptionsFor')
            ->andReturn(null);

        $numberOfTokens = $this->service->getMaxNumberOfTokensFor($institution);

        // One is configured as the globally set application default
        $expectedNumberOfTokens = 13;
        $this->assertEquals($expectedNumberOfTokens, $numberOfTokens);
    }

    private function buildConfigurationOption(int $expectedNumberOfTokens): InstitutionConfigurationOptions&MockInterface
    {
        $numberOfTokensOptionMock = m::mock(NumberOfTokensPerIdentityOption::class);
        $numberOfTokensOptionMock
            ->shouldReceive('getNumberOfTokensPerIdentity')
            ->andReturn($expectedNumberOfTokens);

        $numberOfTokensOptionMock
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn($expectedNumberOfTokens > NumberOfTokensPerIdentityOption::DISABLED);

        $institutionConfigMock = m::mock(InstitutionConfigurationOptions::class);
        $institutionConfigMock->makePartial();

        $institutionConfigMock->numberOfTokensPerIdentityOption = $numberOfTokensOptionMock;

        return $institutionConfigMock;
    }
}
