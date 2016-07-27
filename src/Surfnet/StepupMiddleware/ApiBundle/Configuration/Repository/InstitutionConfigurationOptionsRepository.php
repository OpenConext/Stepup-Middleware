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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;

final class InstitutionConfigurationOptionsRepository extends EntityRepository
{
    /**
     * @param Institution $institution
     * @return InstitutionConfigurationOptions
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findConfigurationOptionsFor(Institution $institution)
    {
        return $this->createQueryBuilder('ico')
            ->where('ico.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param InstitutionConfigurationOptions $institutionConfigurationOptions
     */
    public function save(InstitutionConfigurationOptions $institutionConfigurationOptions)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($institutionConfigurationOptions);
        $entityManager->flush();
    }
}
