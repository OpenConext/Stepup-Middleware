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

use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\SecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;

class SecondFactorService
{
    /**
     * @var SecondFactorRepository
     */
    private $all;

    /**
     * @var UnverifiedSecondFactorRepository
     */
    private $unverifieds;

    /**
     * @param SecondFactorRepository $all
     * @param UnverifiedSecondFactorRepository $unverifieds
     */
    public function __construct(SecondFactorRepository $all, UnverifiedSecondFactorRepository $unverifieds)
    {
        $this->all = $all;
        $this->unverifieds = $unverifieds;
    }

    /**
     * @param string $identityId
     * @return SecondFactor[]
     */
    public function findByIdentity($identityId)
    {
        return $this->all->findByIdentity($identityId);
    }

    /**
     * @param string $identityId
     * @return SecondFactor[]
     */
    public function findUnverifiedByIdentity($identityId)
    {
        return $this->unverifieds->findByIdentity($identityId);
    }
}
