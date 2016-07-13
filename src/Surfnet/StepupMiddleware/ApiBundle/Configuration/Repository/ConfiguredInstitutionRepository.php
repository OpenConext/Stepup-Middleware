<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;

final class ConfiguredInstitutionRepository extends EntityRepository
{
    /**
     * @param ConfiguredInstitution $configuredInstitution
     */
    public function save(ConfiguredInstitution $configuredInstitution)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($configuredInstitution);
        $entityManager->flush();
    }

    /**
     * @param Institution $institution
     * @return bool
     */
    public function hasConfigurationFor(Institution $institution)
    {
        $result = $this->createQueryBuilder('ci')
            ->select('ci.institution')
            ->where('ci.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}
