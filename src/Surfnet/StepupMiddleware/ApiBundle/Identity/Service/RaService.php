<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Ra;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Raa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;

class RaService extends AbstractSearchService
{
    /**
     * @var RaRepository
     */
    private $raRepository;

    /**
     * @var RaaRepository
     */
    private $raaRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        RaRepository $raRepository,
        RaaRepository $raaRepository,
        IdentityRepository $identityRepository
    ) {
        $this->raRepository = $raRepository;
        $this->raaRepository = $raaRepository;
        $this->identityRepository = $identityRepository;
    }

    /**
     * Lists all RAs (this includes RAAs). Only includes RA(A)s that have an associated Identity.
     *
     * @param Institution $institution
     * @return RegistrationAuthorityCredentials[]
     */
    public function listRas(Institution $institution)
    {
        $ras = $this->raRepository->findByInstitution($institution);
        $raas = $this->raaRepository->findByInstitution($institution);

        $nameIds = array_merge(
            array_map(function (Ra $ra) {
                return $ra->nameId;
            }, $ras),
            array_map(function (Raa $raa) {
                return $raa->nameId;
            }, $raas)
        );

        $identities = $this->identityRepository->findByNameIdsIndexed($nameIds);
        $credentials = [];

        foreach ($ras as $ra) {
            if (!isset($identities[$ra->nameId])) {
                continue;
            }

            $credentials[] = RegistrationAuthorityCredentials::fromRa($ra, $identities[$ra->nameId]);
        }

        foreach ($raas as $raa) {
            if (!isset($identities[$raa->nameId])) {
                continue;
            }

            $credentials[] = RegistrationAuthorityCredentials::fromRaa($raa, $identities[$raa->nameId]);
        }

        return $credentials;
    }
}
