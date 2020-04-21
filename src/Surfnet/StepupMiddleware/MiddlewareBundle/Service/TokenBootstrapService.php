<?php

/**
 * Copyright 2020 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Doctrine\ORM\NonUniqueResultException;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Value\Institution as IdentityInstitution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;

/**
 * Encapsulates some ApiBundle repositories to aid the bootstrapping of identities including second factor tokens
 * for test
 */
class TokenBootstrapService
{
    /** @var IdentityRepository  */
    private $identityRepository;
    /** @var UnverifiedSecondFactorRepository  */
    private $unverifiedSecondFactorRepository;
    /** @var VerifiedSecondFactorRepository */
    private $verifiedSecondFactorRepository;
    /** @var InstitutionConfigurationOptionsRepository */
    private $institutionConfigurationRepository;

    public function __construct(
        IdentityRepository $identityRepository,
        UnverifiedSecondFactorRepository $unverifiedSecondFactorRepository,
        VerifiedSecondFactorRepository $verifiedSecondFactorRepository,
        InstitutionConfigurationOptionsRepository $institutionConfigurationOptionsRepository
    ) {
        $this->identityRepository = $identityRepository;
        $this->unverifiedSecondFactorRepository = $unverifiedSecondFactorRepository;
        $this->verifiedSecondFactorRepository = $verifiedSecondFactorRepository;
        $this->institutionConfigurationRepository = $institutionConfigurationOptionsRepository;
    }

    /**
     * @param $actorId
     * @return Identity|null
     */
    public function findIdentityById($actorId)
    {
        return $this->identityRepository->findOneBy(['id' => $actorId]);
    }

    /**
     * @param $institution
     * @return InstitutionConfigurationOptions
     * @throws NonUniqueResultException
     */
    public function findConfigurationOptionsFor($institution)
    {
        return $this->institutionConfigurationRepository->findConfigurationOptionsFor(new Institution($institution));
    }

    /**
     * @param $identityId
     * @param $tokenType
     * @return UnverifiedSecondFactor|null
     */
    public function findUnverifiedToken($identityId, $tokenType)
    {
        return $this->unverifiedSecondFactorRepository->findOneBy(
            ['identityId' => $identityId, 'type' => $tokenType]
        );
    }

    /**
     * @param $identityId
     * @param $tokenType
     * @return VerifiedSecondFactor|null
     */
    public function findVerifiedToken($identityId, $tokenType)
    {
        return $this->verifiedSecondFactorRepository->findOneBy(
            ['identityId' => $identityId, 'type' => $tokenType]
        );
    }

    /**
     * @param NameId $nameId
     * @param IdentityInstitution $institution
     * @return Identity
     */
    public function findOneByNameIdAndInstitution(NameId $nameId, IdentityInstitution $institution)
    {
        return $this->identityRepository->findOneByNameIdAndInstitution($nameId, $institution);
    }

    public function hasIdentityWithNameIdAndInstitution(NameId $nameId, IdentityInstitution $institution)
    {
        return $this->identityRepository->hasIdentityWithNameIdAndInstitution($nameId, $institution);
    }
}
