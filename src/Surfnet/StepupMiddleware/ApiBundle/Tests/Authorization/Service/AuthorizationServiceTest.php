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

use Doctrine\Common\Collections\ArrayCollection;
use Mockery as m;
use Mockery\MockInterface;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\IdentitySelfAssertedTokenOptions;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RecoveryTokenService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;

class AuthorizationServiceTest extends TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private MockInterface&IdentityService $identityService;

    private MockInterface&InstitutionConfigurationOptionsService $institutionConfigurationService;

    private MockInterface&SecondFactorService $secondFactorService;

    private MockInterface&RecoveryTokenService $recoveryTokenService;

    private AuthorizationService $service;

    protected function setUp(): void
    {
        $this->identityService = m::mock(IdentityService::class);
        $this->institutionConfigurationService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->secondFactorService = m::mock(SecondFactorService::class);
        $this->recoveryTokenService = m::mock(RecoveryTokenService::class);

        $this->service = new AuthorizationService(
            $this->identityService,
            $this->institutionConfigurationService,
            $this->secondFactorService,
            $this->recoveryTokenService,
        );
    }

    public function test_it_rejects_unknown_user(): void
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

    public function test_it_rejects_unknown_institution_configuration(): void
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
        $this->assertEquals(
            'Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled',
            reset($messages),
        );
    }

    public function test_it_rejects_disabled_self_asserted_tokens_feature_flag_on_institution(): void
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

    public function test_it_rejects_when_identity_has_vetted_token(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = null;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = false;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals('Identity already has a vetted second factor', reset($messages));
    }

    public function test_it_rejects_when_identity_had_prior_non_sat_token(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = false;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = false;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals(
            'Identity never possessed a self-asserted token, but did/does possess one of the other types',
            reset($messages),
        );
    }

    public function test_recovery_tokens_never_owned_a_sat_token_but_did_own_other_token_type(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = false;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $decision = $this->service->assertRecoveryTokensAreAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals(
            'Identity never possessed a self-asserted token, deny access to recovery token CRUD actions',
            reset($messages),
        );
    }

    public function test_you_cant_sat_when_you_lost_both_rt_and_sf_tokens(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = null;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = true;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $this->recoveryTokenService
            ->shouldReceive('identityHasActiveRecoveryToken')
            ->with($identity)
            ->once()
            ->andReturnFalse();

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals(
            'Identity lost both Recovery and Second Factor token, SAT is not allowed',
            reset($messages),
        );
    }

    public function test_recovery_tokens_all_requirements_met(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = true;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $decision = $this->service->assertRecoveryTokensAreAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }

    public function test_it_allows_when_identity_meets_all_requirements(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = null;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = false;
        $satOptions->possessedSelfAssertedToken = false;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $this->recoveryTokenService
            ->shouldReceive('identityHasActiveRecoveryToken')
            ->with($identity)
            ->once()
            ->andReturnTrue();

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }

    public function test_it_allows_when_identity_with_prior_sat_meets_all_requirements(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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

        $satOptions = new IdentitySelfAssertedTokenOptions();
        $satOptions->possessedToken = true;
        $satOptions->possessedSelfAssertedToken = true;

        $this->identityService
            ->shouldReceive('getSelfAssertedTokenRegistrationOptions')
            ->once()
            ->andReturn($satOptions);

        $this->recoveryTokenService
            ->shouldReceive('identityHasActiveRecoveryToken')
            ->with($identity)
            ->once()
            ->andReturnTrue();

        $decision = $this->service->assertRegistrationOfSelfAssertedTokensIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }

    public function test_it_allows_self_vetting_when_one_sat_present(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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

        $vettedSecondFactor = m::mock(VettedSecondFactor::class);
        $vettedSecondFactor->vettingType = VettingType::TYPE_SELF_ASSERTED_REGISTRATION;

        $collection = m::mock(Pagerfanta::class);
        $collection->shouldReceive('getIterator')->andReturn(new ArrayCollection([$vettedSecondFactor]));

        $this->secondFactorService
            ->shouldReceive('searchVettedSecondFactors')
            ->andReturn($collection);

        $decision = $this->service->assertSelfVetUsingSelfAssertedTokenIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }

    public function test_it_allows_self_vetting_when_multiple_sat_present(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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
        $vettedSecondFactor = m::mock(VettedSecondFactor::class);
        $vettedSecondFactor->vettingType = VettingType::TYPE_SELF_ASSERTED_REGISTRATION;

        $collection = m::mock(Pagerfanta::class);
        $collection->shouldReceive('getIterator')->andReturn(new ArrayCollection([$vettedSecondFactor, $vettedSecondFactor]));

        $this->secondFactorService
            ->shouldReceive('searchVettedSecondFactors')
            ->andReturn($collection);

        $decision = $this->service->assertSelfVetUsingSelfAssertedTokenIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(200, $decision->getCode());
        $this->assertEmpty($messages);
    }

    public function test_it_denies_self_vetting_when_other_vetting_type(): void
    {
        $identity = new Identity();
        $identity->institution = new Institution('Known institution');
        $identity->possessedSelfAssertedToken = true;

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

        $vettedSecondFactor = m::mock(VettedSecondFactor::class);
        $vettedSecondFactor->vettingType = VettingType::TYPE_ON_PREMISE;

        $collection = m::mock(Pagerfanta::class);
        $collection->shouldReceive('getIterator')->andReturn(new ArrayCollection([$vettedSecondFactor, $vettedSecondFactor]));

        $this->secondFactorService
            ->shouldReceive('searchVettedSecondFactors')
            ->andReturn($collection);

        $decision = $this->service->assertSelfVetUsingSelfAssertedTokenIsAllowed($identityId);
        $messages = $decision->getErrorMessages();

        $this->assertEquals(403, $decision->getCode());
        $this->assertEquals(
            'Self-vetting using SAT is only allowed when only SAT tokens are in possession',
            reset($messages),
        );
    }
}
