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
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\AuthorizationDecision;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VettedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RecoveryTokenService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;

/**
 * Perform authorization checks
 * For example, test if an identity is allowed to register self-asserted tokens.
 *
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 */
class AuthorizationService
{
    public function __construct(
        private readonly IdentityService $identityService,
        private readonly InstitutionConfigurationOptionsService $institutionConfigurationService,
        private readonly SecondFactorService $secondFactorService,
        private readonly RecoveryTokenService $recoveryTokenService
    ) {
    }

    /**
     * Is an identity is allowed to register a self asserted token.
     *
     * An identity can register a SAT when:
     * - The institution of the identity allows SAT
     * - Has not yet registered a SAT
     * - Has not possessed a non SAT token previously.
     * - It did not lose both the recovery token and self-asserted token
     */
    public function assertRegistrationOfSelfAssertedTokensIsAllowed(IdentityId $identityId): AuthorizationDecision
    {
        $identity = $this->findIdentity($identityId);
        if (!$identity instanceof Identity) {
            return $this->deny('Identity not found');
        }
        $institutionConfiguration = $this->findInstitutionConfiguration($identity);

        if (!$institutionConfiguration instanceof InstitutionConfigurationOptions) {
            return $this->deny(
                'Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled',
            );
        }

        if (!$institutionConfiguration->selfAssertedTokensOption->isEnabled()) {
            return $this->deny(
                sprintf('Institution "%s", does not allow self-asserted tokens', (string)$identity->institution),
            );
        }

        $hasVettedSecondFactorToken = $this->secondFactorService->hasVettedByIdentity($identityId);

        $options = $this->identityService->getSelfAssertedTokenRegistrationOptions(
            $identity,
            $hasVettedSecondFactorToken,
        );

        if ($hasVettedSecondFactorToken) {
            return $this->deny('Identity already has a vetted second factor');
        }

        // Only allow self-asserted token (SAT) if the user does not have a token yet, or the first
        // registered token was a SAT.
        $hadOtherTokenType = $options->possessedSelfAssertedToken === false && $options->possessedToken;
        if ($hadOtherTokenType) {
            return $this->deny(
                'Identity never possessed a self-asserted token, but did/does possess one of the other types',
            );
        }
        // The Identity is not allowed to do a SAT when he had a RT, but lost it. And also currently has no SF
        $hasActiveRecoveryToken = $this->recoveryTokenService->identityHasActiveRecoveryToken($identity);
        if ($options->possessedSelfAssertedToken && !$hasActiveRecoveryToken) {
            return $this->deny('Identity lost both Recovery and Second Factor token, SAT is not allowed');
        }

        return $this->allow();
    }

    /**
     * Is an identity allowed to self vet using a self-asserted token?
     *
     * One is allowed to do so when:
     *  - SAT is allowed for the institution of the identity
     *  - All the tokens of the identity are vetted using the SAT vetting type
     */
    public function assertSelfVetUsingSelfAssertedTokenIsAllowed(IdentityId $identityId): AuthorizationDecision
    {
        $identity = $this->findIdentity($identityId);
        if (!$identity instanceof Identity) {
            return $this->deny('Identity not found');
        }
        $institutionConfiguration = $this->findInstitutionConfiguration($identity);

        if (!$institutionConfiguration instanceof InstitutionConfigurationOptions) {
            return $this->deny(
                'Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled',
            );
        }

        if (!$institutionConfiguration->selfAssertedTokensOption->isEnabled()) {
            return $this->deny(
                sprintf('Institution "%s", does not allow self-asserted tokens', (string)$identity->institution),
            );
        }

        $query = new VettedSecondFactorQuery();
        $query->identityId = $identityId;
        $query->pageNumber = 1;
        $tokens = $this->secondFactorService->searchVettedSecondFactors($query);
        foreach ($tokens->getIterator() as $vettedToken) {
            if ($vettedToken->vettingType !== VettingType::TYPE_SELF_ASSERTED_REGISTRATION) {
                return $this->deny('Self-vetting using SAT is only allowed when only SAT tokens are in possession');
            }
        }

        return $this->allow();
    }

    /**
     * Is an identity allowed to register recovery tokens?
     *
     * One is allowed to do so when:
     *  - SAT is allowed for the institution of the identity
     *  - Identity must possess a SAT (or did so at one point)
     */
    public function assertRecoveryTokensAreAllowed(IdentityId $identityId): AuthorizationDecision
    {
        $identity = $this->findIdentity($identityId);
        if (!$identity instanceof Identity) {
            return $this->deny('Identity not found');
        }
        $institutionConfiguration = $this->findInstitutionConfiguration($identity);

        if (!$institutionConfiguration instanceof InstitutionConfigurationOptions) {
            return $this->deny(
                'Institution configuration could not be found, unable to ascertain if self-asserted tokens feature is enabled',
            );
        }

        if (!$institutionConfiguration->selfAssertedTokensOption->isEnabled()) {
            return $this->deny(
                sprintf('Institution "%s", does not allow self-asserted tokens', (string)$identity->institution),
            );
        }

        // Only allow CRUD actions on recovery tokens when the identity previously registered a SAT
        $options = $this->identityService->getSelfAssertedTokenRegistrationOptions(
            $identity,
            $this->secondFactorService->hasVettedByIdentity($identityId),
        );
        if ($options->possessedSelfAssertedToken === false) {
            return $this->deny(
                'Identity never possessed a self-asserted token, deny access to recovery token CRUD actions',
            );
        }

        return $this->allow();
    }

    private function findInstitutionConfiguration(Identity $identity): ?InstitutionConfigurationOptions
    {
        $institution = new Institution((string)$identity->institution);
        return $this->institutionConfigurationService
            ->findInstitutionConfigurationOptionsFor($institution);
    }

    private function findIdentity(IdentityId $identityId): ?Identity
    {
        return $this->identityService->find((string)$identityId);
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
