<?php

/**
 * Copyright 2017 SURFnet B.V.
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
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\AllowedSecondFactor;

final class AllowedSecondFactorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AllowedSecondFactor::class);
    }

    public function save(AllowedSecondFactor $allowedSecondFactor): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($allowedSecondFactor);
        $entityManager->flush();
    }

    /**
     * @return AllowedSecondFactor[]
     */
    public function getAllowedSecondFactorsFor(Institution $institution): array
    {
        return $this->createQueryBuilder('asf')
            ->select()
            ->where('asf.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->execute();
    }

    public function clearAllowedSecondFactorListFor(Institution $institution): void
    {
        $this->createQueryBuilder('asf')
            ->delete()
            ->where('asf.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->execute();
    }
}
