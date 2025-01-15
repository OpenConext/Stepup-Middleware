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
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionAuthorizationService;

class InstitutionAuthorizationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private InstitutionAuthorizationService $service;

    private InstitutionAuthorizationRepository&MockInterface $repository;

    public function setUp(): void
    {
        $this->repository = m::mock(InstitutionAuthorizationRepository::class);

        $this->service = new InstitutionAuthorizationService(
            $this->repository,
        );
    }

    /**
     * Simulates the use case where an institution does have a specific institution config, but the token setting is
     * disabled.
     */
    public function test_get_institution_options_from_service(): void
    {
        $institution = new Institution('surfnet.nl');

        $expectedInstitutions = [
            'institution-a',
            'institution-b',
            'institution-c',
        ];
        $expectedAuthorizations = $this->buildAuthorizations($expectedInstitutions);

        $this->repository
            ->shouldReceive('findAuthorizationOptionsForInstitutionByRole')
            ->andReturn($expectedAuthorizations);

        $institutionOptions = $this->service->findAuthorizationsByRoleFor($institution, InstitutionRole::useRa());

        $this->assertEquals($institutionOptions->getInstitutions($institution), $expectedInstitutions);
        $this->assertEquals(InstitutionRole::useRa(), $institutionOptions->getInstitutionRole());
    }

    /**
     * @return mixed[]
     */
    private function buildAuthorizations(array $expectedInstitutions): array
    {
        $authorizations = [];
        foreach ($expectedInstitutions as $institution) {
            $authorizationMock = m::mock(InstitutionAuthorization::class);
            $authorizationMock->makePartial();

            $authorizationMock->institutionRelation = new Institution($institution);

            $authorizations[] = $authorizationMock;
        }

        return $authorizations;
    }
}
