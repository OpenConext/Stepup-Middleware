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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;

class InstitutionConfigurationOptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionConfigurationOptions::class);
    }

    /**
     * @param Institution $institution
     * @return InstitutionConfigurationOptions
     * @throws NonUniqueResultException
     */
    public function findConfigurationOptionsFor(Institution $institution)
    {
        return $this->createQueryBuilder('ico')
            ->where('ico.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param InstitutionConfigurationOptions $institutionConfigurationOptions
     */
    public function save(InstitutionConfigurationOptions $institutionConfigurationOptions): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($institutionConfigurationOptions);
        $entityManager->flush();
    }

    /**
     * @param Institution $institution
     */
    public function removeConfigurationOptionsFor(Institution $institution): void
    {
        $this->createQueryBuilder('ico')
            ->delete()
            ->where('ico.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->execute();
    }
}
