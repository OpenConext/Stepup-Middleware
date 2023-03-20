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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Authorization\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;

class AuthorizationContextServiceTest extends TestCase
{
    /**
     * @var AuthorizationContextService
     */
    private $service;

    /**
     * @var IdentityService|m\Mock
     */
    private $identityService;

    /**
     * @var SraaService|m\Mock
     */
    private $sraaService;

    /**
     * @var AuthorizationRepository|m\Mock
     */
    private $authorizationRepository;
    /**
     * @var m\Mock&ConfiguredInstitutionRepository
     */
    private $institutionRepo;

    public function setUp(): void
    {
        $identityService = m::mock(IdentityService::class);
        $sraaService = m::mock(SraaService::class);
        $authorizationRepository = m::mock(AuthorizationRepository::class);
        $this->institutionRepo = m::mock(ConfiguredInstitutionRepository::class);
        $service = new AuthorizationContextService(
            $sraaService,
            $identityService,
            $this->institutionRepo,
            $authorizationRepository
        );

        $this->identityService = $identityService;
        $this->sraaService = $sraaService;
        $this->service = $service;
        $this->authorizationRepository = $authorizationRepository;
    }

    /**
     * @test
     * @group domain
     */
    public function it_can_build_a_context()
    {
        $actorInstitution = new Institution('institution-a');
        $role = RegistrationAuthorityRole::raa();

        $arbitraryId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $arbitraryNameId = new NameId('urn:collab:person:stepup.example.com:joe-a1');

        $institutions = new InstitutionCollection([
            new Institution('institution-a.example.com'),
            new Institution('institution-d.example.com'),
        ]);

        $identity = Identity::create(
            $arbitraryId,
            $actorInstitution,
            $arbitraryNameId,
            new Email('foo@bar.com'),
            new CommonName('Foobar'),
            new Locale('en_GB')
        );

        $identityId = new IdentityId($arbitraryId);

        $this->identityService
            ->shouldReceive('find')
            ->with($arbitraryId)
            ->andReturn($identity);

        $this->sraaService
            ->shouldReceive('findByNameId')
            ->with($arbitraryNameId)
            ->andReturn(null);

        $this->authorizationRepository
            ->shouldReceive('getInstitutionsForRole')
            ->withArgs([$role, $identityId])
            ->andReturn($institutions);

        $context = $this->service->buildInstitutionAuthorizationContext(
            $identityId,
            $role
        );

        $this->assertEquals($institutions, $context->getInstitutions());
        $this->assertFalse($context->isActorSraa());
    }

    /**
     * @test
     * @group domain
     */
    public function it_can_build_a_context_with_sraa_actor()
    {
        $actorInstitution = new Institution('institution-a');
        $role = RegistrationAuthorityRole::raa();

        $sraaId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $adminNameId = new NameId('urn:collab:person:stepup.example.com:admin');

        $institutions = new InstitutionCollection([
            new Institution('institution-a.example.com'),
            new Institution('institution-d.example.com'),
        ]);

        $sraaIdentity = Identity::create(
            $sraaId,
            $actorInstitution,
            $adminNameId,
            new Email('foo@bar.com'),
            new CommonName('Foobar'),
            new Locale('en_GB')
        );
        $sraa = m::mock(Sraa::class);

        $identityId = new IdentityId($sraaId);

        $this->identityService
            ->shouldReceive('find')
            ->with($sraaId)
            ->andReturn($sraaIdentity);

        $this->sraaService
            ->shouldReceive('findByNameId')
            ->with($adminNameId)
            ->andReturn($sraa);

        $this->authorizationRepository
            ->shouldReceive('getInstitutionsForRole')
            ->withArgs([$role, $identityId])
            ->andReturn($institutions);

        $configuredInstitutions = [];
        foreach ($institutions as $institution) {
            $ci = new ConfiguredInstitution();
            $ci->institution = $institution->getInstitution();
            $configuredInstitutions[] = $ci;
        }
        $this->institutionRepo->shouldReceive('findAll')->andReturn($configuredInstitutions);

        $context = $this->service->buildInstitutionAuthorizationContext(
            $identityId,
            $role
        );

        $this->assertEquals($institutions, $context->getInstitutions());
        $this->assertTrue($context->isActorSraa());
    }

    public function test_it_can_retrieve_select_raa_institutions()
    {
        $actorInstitution = new Institution('institution-a');

        $arbitraryId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $arbitraryNameId = new NameId('urn:collab:person:stepup.example.com:joe-a1');

        $institutions = new InstitutionCollection([
            new Institution('institution-a.example.com'),
            new Institution('institution-d.example.com'),
        ]);

        $identity = Identity::create(
            $arbitraryId,
            $actorInstitution,
            $arbitraryNameId,
            new Email('foo@bar.com'),
            new CommonName('Foobar'),
            new Locale('en_GB')
        );

        $identityId = new IdentityId($arbitraryId);

        $this->identityService
            ->shouldReceive('find')
            ->with($arbitraryId)
            ->andReturn($identity);

        $this->sraaService
            ->shouldReceive('findByNameId')
            ->with($arbitraryNameId)
            ->andReturn(null);

        $this->authorizationRepository
            ->shouldReceive('getInstitutionsForSelectRaaRole')
            ->withArgs([$identityId])
            ->andReturn($institutions);

        $context = $this->service->buildSelectRaaInstitutionAuthorizationContext(
            $identityId
        );

        $this->assertEquals($institutions, $context->getInstitutions());
        $this->assertFalse($context->isActorSraa());
    }

    /**
     * @test
     * @group domain
     */
    public function it_rejects_unknown_actor()
    {
        $this->expectExceptionMessage("The provided id is not associated with any known identity");
        $this->expectException(\Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException::class);

        $role = RegistrationAuthorityRole::raa();

        $actorId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $this->identityService
            ->shouldReceive('find')
            ->with($actorId)
            ->andReturn(null);

        $this->service->buildInstitutionAuthorizationContext(
            new IdentityId($actorId),
            $role
        );
    }
}
