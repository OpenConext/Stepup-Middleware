<?php

/**
 * Copyright 2022 SURFnet B.V.
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
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;

class AuthorizationServiceTest extends TestCase
{
    /**
     * @var m\MockInterface|IdentityService
     */
    private $identityService;

    /**
     * @var m\MockInterface|InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationService;

    /**
     * @var m\MockInterface|SecondFactorService
     */
    private $secondFactorService;

    /**
     * @var AuthorizationService
     */
    private $service;

    protected function setUp(): void
    {
        $this->identityService = m::mock(IdentityService::class);
        $this->institutionConfigurationService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->secondFactorService = m::mock(SecondFactorService::class);

        $this->service = new AuthorizationService(
            $this->identityService,
            $this->institutionConfigurationService,
            $this->secondFactorService
        );
    }

    public function test_it_rejects_unknown_user()
    {
        $this->identityService
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed(new IdentityId('unknown-user-id'));
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals('Identity not found', reset($messages));
    }

    public function test_it_rejects_unknown_institution_configuration()
    {
        $identity = new Identity();
        $identity->institution = new Institution('Unknown institution');

        $this->identityService
            ->shouldReceive('find')
            ->once()
            ->andReturn($identity);

        $this->institutionConfigurationService
            ->shouldReceive('findInstitutionConfigurationOptionsFor')
            ->once()
            ->andReturn(null);

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed(new IdentityId('known-user-id'));
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals('Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled', reset($messages));
    }

    public function test_it_rejects_disabled_self_asserted_tokens_feature_flag_on_institution()
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');

        $this->identityService
            ->shouldReceive('find')
            ->once()
            ->andReturn($identity);

        $options = new InstitutionConfigurationOptions();
        $options->selfAssertedTokensOption = new SelfAssertedTokensOption(false);
        $this->institutionConfigurationService
            ->shouldReceive('findInstitutionConfigurationOptionsFor')
            ->once()
            ->andReturn($options);

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed(new IdentityId('known-user-id'));
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals('Institution "known institution", does not allow self-asserted tokens', reset($messages));
    }

    public function test_it_rejects_when_identity_has_vetted_token()
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');

        $this->identityService
            ->shouldReceive('find')
            ->once()
            ->andReturn($identity);

        $options = new InstitutionConfigurationOptions();
        $options->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->institutionConfigurationService
            ->shouldReceive('findInstitutionConfigurationOptionsFor')
            ->once()
            ->andReturn($options);

        $identityId = new IdentityId('known-user-id');
        $this->secondFactorService
            ->shouldReceive('hasVettedByIdentity')
            ->with($identityId)
            ->andReturnTrue();

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals('Identity already has a vetted second factor', reset($messages));
    }

    public function test_it_allows_when_identity_meets_all_requirements()
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');

        $this->identityService
            ->shouldReceive('find')
            ->once()
            ->andReturn($identity);

        $options = new InstitutionConfigurationOptions();
        $options->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->institutionConfigurationService
            ->shouldReceive('findInstitutionConfigurationOptionsFor')
            ->once()
            ->andReturn($options);

        $identityId = new IdentityId('known-user-id');
        $this->secondFactorService
            ->shouldReceive('hasVettedByIdentity')
            ->with($identityId)
            ->andReturnFalse();

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }


}