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

namespace Surfnet\StepupMiddleware\GatewayBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\SecondFactor;

class SecondFactorRepository extends EntityRepository
{
    /**
     * @param SecondFactor $secondFactor
     */
    public function save(SecondFactor $secondFactor)
    {
        $this->getEntityManager()->persist($secondFactor);
        $this->getEntityManager()->flush();
    }

    /**
     * @param SecondFactorId $secondFactorId
     * @return SecondFactor|null
     */
    public function findOneBySecondFactorId(SecondFactorId $secondFactorId)
    {
        return $this->findOneBy(['secondFactorId' => (string) $secondFactorId]);
    }

    /**
     * @param IdentityId $identityId
     * @return SecondFactor[]
     */
    public function findByIdentityId(IdentityId $identityId)
    {
        return $this->findBy(['identityId' => (string) $identityId]);
    }

    public function removeByIdentityId(IdentityId $identityId)
    {
        $secondFactors = $this->findByIdentityId($identityId);
        $entityManager = $this->getEntityManager();

        foreach ($secondFactors as $secondFactor) {
            $entityManager->remove($secondFactor);
        }

        $entityManager->flush();
    }

    /**
     * @param SecondFactor $secondFactor
     */
    public function remove(SecondFactor $secondFactor)
    {
        $this->getEntityManager()->remove($secondFactor);
        $this->getEntityManager()->flush();
    }
}
