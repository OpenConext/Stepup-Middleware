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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Service;

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\AuthorizationDecision;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;

/**
 * Perform authorization checks
 * For example, test if an identity is allowed to register self-asserted tokens.
 */
class AuthorizationService
{
    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @var InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationService;

    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    public function __construct(
        IdentityService $identityService,
        InstitutionConfigurationOptionsService $institutionConfigurationService,
        SecondFactorService $secondFactorService
    ) {
        $this->identityService = $identityService;
        $this->institutionConfigurationService = $institutionConfigurationService;
        $this->secondFactorService = $secondFactorService;
    }

    public function assertRegistrationOfSelfAssertedTokensIsAllowed(IdentityId $identityId): AuthorizationDecision
    {
        $identity = $this->identityService->find((string)$identityId);
        if (!$identity) {
            return $this->deny('Identity not found');
        }

        $institution = new Institution((string)$identity->institution);
        $institutionConfiguration = $this->institutionConfigurationService
            ->findInstitutionConfigurationOptionsFor($institution);
        if (!$institutionConfiguration) {
            return $this->deny('Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled');
        }

        if (!$institutionConfiguration->selfAssertedTokensOption->isEnabled()) {
            return $this->deny(sprintf('Institution "%s", does not allow self-asserted tokens', (string) $identity->institution));
        }

        $hasVettedSecondFactorToken = $this->secondFactorService->hasVettedByIdentity($identityId);

        $options = $this->identityService->getSelfAssertedTokenRegistrationOptions(
            $identity,
            $hasVettedSecondFactorToken
        );

        if ($hasVettedSecondFactorToken) {
            return $this->deny('Identity already has a vetted second factor');
        }

        // Only allow self-asserted token (SAT) if the user does not have a token yet, or the first
        // registered token was a SAT.
        $hadOtherTokenType = $options->possessedSelfAssertedToken === false && $options->possessedToken === true;
        if ($hadOtherTokenType) {
            return $this->deny('Identity never possessed a self-asserted token, but did/does possess one of the other types');
        }

        return $this->allow();
    }

    public function assertRecoveryTokensAreAllowed(IdentityId $identityId): AuthorizationDecision
    {
        $identity = $this->identityService->find((string)$identityId);
        if (!$identity) {
            return $this->deny('Identity not found');
        }

        $institution = new Institution((string)$identity->institution);
        $institutionConfiguration = $this->institutionConfigurationService
            ->findInstitutionConfigurationOptionsFor($institution);
        if (!$institutionConfiguration) {
            return $this->deny('Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled');
        }

        if (!$institutionConfiguration->selfAssertedTokensOption->isEnabled()) {
            return $this->deny(sprintf('Institution "%s", does not allow self-asserted tokens', (string) $identity->institution));
        }

        // Only allow CRUD actions on recovery tokens when the identity previously registered a SAT
        $options = $this->identityService->getSelfAssertedTokenRegistrationOptions(
            $identity,
            $this->secondFactorService->hasVettedByIdentity($identityId)
        );
        if ($options->possessedSelfAssertedToken === false) {
            return $this->deny('Identity never possessed a self-asserted token, deny access to recovery token CRUD actions');
        }

        return $this->allow();
    }

    private function deny(string $errorMessage): AuthorizationDecision
    {
        return AuthorizationDecision::denied([$errorMessage]);
    }

    private function allow(): AuthorizationDecision
    {
        return AuthorizationDecision::allowed();
    }
}
