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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationContextFactory;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSetInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Symfony\Component\HttpFoundation\Request;

class InstitutionAuthorizationContextFactoryTest extends TestCase
{
    /**
     * @var InstitutionAuthorizationContextFactory|m\Mock
     */
    private $factory;

    /**
     * @var IdentityService|m\Mock
     */
    private $service;

    public function setUp()
    {
        $service = m::mock(IdentityService::class);
        $factory = new InstitutionAuthorizationContextFactory($service);

        $this->service = $service;
        $this->factory = $factory;
    }

    /**
     * @test
     * @group domain
     */
    public function it_can_build_a_context_from_a_well_configured_request()
    {
        $institution = 'institution-a';

        /** @var Identity $actorInstitution */
        $actorIdentity = m::mock(Identity::class)->makePartial();
        $actorIdentity->institution = new Institution($institution);
        $this->service->shouldReceive('find')->andReturn($actorIdentity);

        $roleSet = m::mock(InstitutionRoleSetInterface::class);

        $context = $this->factory->buildFrom(
            $this->configureRequest('12345678-1234-1234-1234-123456789101', 'institution-a'),
            $roleSet
        );
        $this->assertInstanceOf(InstitutionAuthorizationContext::class, $context);
    }

    /**
     * @test
     * @group domain
     */
    public function it_only_builds_when_required_parameters_are_present()
    {
        $institution = 'institution-a';
        $roleSet = m::mock(InstitutionRoleSetInterface::class);

        $context = $this->factory->buildFrom($this->configureRequest($institution), $roleSet);
        $this->assertNull($context);
    }

    /**
     * @test
     * @group domain
     * @dataProvider faultyAuthrizationContextProvider
     *
     * @expectedException \Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException
     */
    public function it_errors_when_invalid_arguments_are_received(
        $requestActorId,
        $requestActorInstitution,
        $actorIdentity
    )
    {
        $this->service->shouldReceive('find')->andReturn($actorIdentity);
        $roleSet = m::mock(InstitutionRoleSetInterface::class);
        $this->factory->buildFrom($this->configureRequest($requestActorId, $requestActorInstitution), $roleSet);
    }

    public function faultyAuthrizationContextProvider()
    {
        /** @var Identity $actorInstitution */
        $actorIdentity = m::mock(Identity::class)->makePartial();
        $actorIdentity->institution = new Institution('institution-a');

        return [
            'institution does not match that of actor institution' => ['12345678-1234-1234-1234-123456789101', 'institution-b', $actorIdentity],
            'invalid actor id is provided' => ['invalid', 'institution-a', null],
        ];
    }

    private function configureRequest($actorId = null, $actorInstitution = null)
    {
        $request = m::mock(Request::class);

        $request->shouldReceive('get')->with('actorId')->andReturn($actorId);
        $request->shouldReceive('get')->with('actorInstitution')->andReturn($actorInstitution);

        return $request;
    }
}
