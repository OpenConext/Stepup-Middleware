<?php

declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\GatewayBundle\Tests\Projector;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Event\SsoOn2faOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SsoRegistrationBypassOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\SsoOn2faOption;
use Surfnet\Stepup\Configuration\Value\SsoRegistrationBypassOption;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\InstitutionConfiguration;
use Surfnet\StepupMiddleware\GatewayBundle\Projector\InstitutionConfigurationProjector;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\InstitutionConfigurationRepository;

class InstitutionConfigurationProjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private InstitutionConfigurationProjector $projector;

    private InstitutionConfigurationRepository&MockInterface $repository;

    protected function setUp(): void
    {
        $repository = m::mock(InstitutionConfigurationRepository::class);
        $projector = new InstitutionConfigurationProjector($repository);
        $this->repository = $repository;
        $this->projector = $projector;
    }

    public function test_create_row_when_non_existent_sso_on_2fa(): void
    {
        $event = new SsoOn2faOptionChangedEvent(
            new InstitutionConfigurationId(Uuid::uuid4()->toString()),
            new Institution('institution-a.nl'),
            new SsoOn2faOption(true),
        );
        $this->repository->shouldReceive('findByInstitution')->once()->with('institution-a.nl')->andReturn(null);
        $this->repository->shouldReceive('save')->once()->withArgs(
            fn(InstitutionConfiguration $configuration,
            ): bool => $configuration->institution === 'institution-a.nl' && $configuration->ssoOn2faEnabled,
        );

        $this->projector->applySsoOn2faOptionChangedEvent($event);
    }

    public function test_updates_existing_row_sso_on_2fa(): void
    {
        $event = new SsoOn2faOptionChangedEvent(
            new InstitutionConfigurationId(Uuid::uuid4()->toString()),
            new Institution('institution-a.nl'),
            new SsoOn2faOption(true),
        );
        $configuration = new InstitutionConfiguration('institution-a.nl', false, false);

        $this->repository->shouldReceive('findByInstitution')->once()->with('institution-a.nl')->andReturn($configuration);
        $this->repository->shouldReceive('save')->once()->withArgs(
            fn(InstitutionConfiguration $configuration,
            ): bool => $configuration->institution === 'institution-a.nl' && $configuration->ssoOn2faEnabled,
        );

        $this->projector->applySsoOn2faOptionChangedEvent($event);
    }

    public function test_create_row_when_non_existent_registration_bypass(): void
    {
        $event = new SsoRegistrationBypassOptionChangedEvent(
            new InstitutionConfigurationId(Uuid::uuid4()->toString()),
            new Institution('institution-a.nl'),
            new SsoRegistrationBypassOption(true),
        );
        $this->repository->shouldReceive('findByInstitution')->once()->with('institution-a.nl')->andReturn(null);
        $this->repository->shouldReceive('save')->once()->withArgs(
            fn(InstitutionConfiguration $configuration,
            ): bool => $configuration->institution === 'institution-a.nl' && $configuration->ssoRegistrationBypass,
        );

        $this->projector->applySsoRegistrationBypassOptionChangedEvent($event);
    }

    public function test_updates_existing_row_registration_bypass(): void
    {
        $event = new SsoRegistrationBypassOptionChangedEvent(
            new InstitutionConfigurationId(Uuid::uuid4()->toString()),
            new Institution('institution-a.nl'),
            new SsoRegistrationBypassOption(true),
        );
        $configuration = new InstitutionConfiguration('institution-a.nl', false, false);

        $this->repository->shouldReceive('findByInstitution')->once()->with('institution-a.nl')->andReturn($configuration);
        $this->repository->shouldReceive('save')->once()->withArgs(
            fn(InstitutionConfiguration $configuration,
            ): bool => $configuration->institution === 'institution-a.nl' && $configuration->ssoRegistrationBypass,
        );

        $this->projector->applySsoRegistrationBypassOptionChangedEvent($event);
    }
}
