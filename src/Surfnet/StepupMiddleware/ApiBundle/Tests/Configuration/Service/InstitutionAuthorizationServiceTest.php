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
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionAuthorizationService;

class InstitutionAuthorizationServiceTest extends TestCase
{
    /**
     * @var InstitutionAuthorizationService
     */
    private $service;

    /**
     * @var InstitutionAuthorizationRepository|Mock
     */
    private $repository;

    public function setUp()
    {
        $this->repository = m::mock(InstitutionAuthorizationRepository::class);

        $this->service = new InstitutionAuthorizationService(
            $this->repository
        );
    }

    /**
     * Simulates the use case where an institution does have a specific institution config, but the token setting is
     * disabled.
     */
    public function test_get_institution_options_from_service()
    {
        $institution = new Institution('surfnet.nl');

        $expectedInstitutions = [
            'institution-a',
            'institution-b',
            'institution-c',
        ];
        $expectedAuthorizations = $this->buildAuthorizations($expectedInstitutions);

        $this->repository
            ->shouldReceive('findAuthorizationOptionsForInstitution')
            ->andReturn($expectedAuthorizations);

        $institutionOptions = $this->service->findAuthorizationsFor($institution, InstitutionRole::useRa());

        $this->assertEquals($institutionOptions->getInstitutionSet()->getInstitutions(), $expectedInstitutions);
        $this->assertEquals(InstitutionRole::useRa(), $institutionOptions->getInstitutionRole());
    }

    private function buildAuthorizations($expectedInstitutions)
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
