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
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationContextFactory;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;

class InstitutionAuthorizationServiceTest extends TestCase
{
    /**
     * @var InstitutionAuthorizationService
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

    public function setUp()
    {
        $identityService = m::mock(IdentityService::class);
        $sraaService = m::mock(SraaService::class);
        $service = new InstitutionAuthorizationService($sraaService, $identityService);

        $this->identityService = $identityService;
        $this->sraaService = $sraaService;
        $this->service = $service;
    }

    /**
     * @test
     * @group domain
     */
    public function it_can_build_a_context()
    {
        $actorInstitution = new Institution('institution-a');
        $roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_USE_RA), new InstitutionRole(InstitutionRole::ROLE_USE_RAA)]
        );

        $arbitraryId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $arbitraryNameId = new NameId('urn:collab:person:stepup.example.com:joe-a1');

        $identity = Identity::create(
            $arbitraryId,
            $actorInstitution,
            $arbitraryNameId,
            new Email('foo@bar.com'),
            new CommonName('Foobar'),
            new Locale('en_GB')
        );

        $this->identityService
            ->shouldReceive('find')
            ->with($arbitraryId)
            ->andReturn($identity);

        $this->sraaService
            ->shouldReceive('findByNameId')
            ->with($arbitraryNameId)
            ->andReturn(null);

        $context = $this->service->buildInstitutionAuthorizationContext(
            $actorInstitution,
            $roleRequirements,
            new IdentityId($arbitraryId)
        );

        $this->assertEquals($actorInstitution, $context->getActorInstitution());
        $this->assertEquals($roleRequirements, $context->getRoleRequirements());
        $this->assertFalse($context->isActorSraa());
    }

    /**
     * @test
     * @group domain
     */
    public function it_can_build_a_context_with_sraa_actor()
    {
        $actorInstitution = new Institution('institution-a');
        $roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_USE_RA), new InstitutionRole(InstitutionRole::ROLE_USE_RAA)]
        );

        $sraaId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $adminNameId = new NameId('urn:collab:person:stepup.example.com:admin');

        $sraaIdentity = Identity::create(
            $sraaId,
            $actorInstitution,
            $adminNameId,
            new Email('foo@bar.com'),
            new CommonName('Foobar'),
            new Locale('en_GB')
        );
        $sraa = m::mock(Sraa::class);

        $this->identityService
            ->shouldReceive('find')
            ->with($sraaId)
            ->andReturn($sraaIdentity);

        $this->sraaService
            ->shouldReceive('findByNameId')
            ->with($adminNameId)
            ->andReturn($sraa);

        $context = $this->service->buildInstitutionAuthorizationContext(
            $actorInstitution,
            $roleRequirements,
            new IdentityId($sraaId)
        );

        $this->assertEquals($actorInstitution, $context->getActorInstitution());
        $this->assertEquals($roleRequirements, $context->getRoleRequirements());
        $this->assertTrue($context->isActorSraa());
    }

    /**
     * @test
     * @group domain
     * @expectedException \Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage The provided id is not associated with any known identity
     */
    public function it_rejects_unknown_actor()
    {
        $actorInstitution = new Institution('institution-a');
        $roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_USE_RA), new InstitutionRole(InstitutionRole::ROLE_USE_RAA)]
        );

        $actorId = 'dc4cc738-5f1c-4d8c-84a2-d6faf8aded89';

        $this->identityService
            ->shouldReceive('find')
            ->with($actorId)
            ->andReturn(null);

        $this->service->buildInstitutionAuthorizationContext(
            $actorInstitution,
            $roleRequirements,
            new IdentityId($actorId)
        );
    }
}
