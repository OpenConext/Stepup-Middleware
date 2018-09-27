<?php

/**
 * Copyright 2018 SURFnet B.V.
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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

class InstitutionAuthorizationRepository extends EntityRepository
{

    /**
     * @param Institution $institution
     * @param InstitutionRole $role
     * @return InstitutionAuthorization[]
     */
    public function findAuthorizationOptionsForInstitution(Institution $institution, InstitutionRole $role)
    {
        return $this->createQueryBuilder('ia')
            ->where('ia.institution = :institution')
            ->andWhere('ia.type = :type')
            ->setParameter('institution', $institution->getInstitution())
            ->setParameter('institutionRole', $role)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param Institution $institution
     * @param InstitutionRole $role
     * @return InstitutionAuthorization|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findInstitutionByAuthorization(Institution $institution, InstitutionRole $role)
    {
        return $this->createQueryBuilder('ia')
            ->where('ia.institutionRelation = :institution')
            ->andWhere('ia.type = :type')
            ->setParameter('institution', $institution->getInstitution())
            ->setParameter('institutionRole', $role)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Institution $institution
     * @param InstitutionAuthorizationOption $institutionOption
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveInstitutionOption(Institution $institution, InstitutionAuthorizationOption $institutionOption)
    {
        $institutionAuthorizations = [];

        $institutionSet = $institutionOption->getInstitutionSet();
        foreach ($institutionSet->getInstitutions() as $relatedInstitution) {
            $institutionAuthorizations[] = InstitutionAuthorization::create(
                $institution,
                $relatedInstitution,
                $institutionOption->getInstitutionRole()
            );
        }

        $this->save($institution, $institutionOption->getInstitutionRole(), $institutionAuthorizations);
    }

    /**
     * @param Institution $institution
     * @param InstitutionRole $role
     * @param InstitutionAuthorization[] $institutionAuthorizations
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function save(Institution $institution, InstitutionRole $role, array $institutionAuthorizations)
    {
        $entityManager = $this->getEntityManager();

        $this->clearOldAuthorizations($entityManager, $institution, $role);
        $this->addNewAuthorizations($entityManager, $role, $institutionAuthorizations);

        $entityManager->flush();
    }

    /**
     * @param EntityManager $entityManager
     * @param Institution $institution
     * @param InstitutionRole $role
     */
    private function clearOldAuthorizations(EntityManager $entityManager, Institution $institution, InstitutionRole $role)
    {
        $entityManager->createQuery(
            'DELETE '.InstitutionAuthorization::class.' ia
            WHERE ia.institutionRole = :role AND ia.institution = :institution'
        )
            ->setParameter('role', $role)
            ->setParameter('institution', $institution->getInstitution())
            ->execute();
    }

    /**
     * @param EntityManager $entityManager
     * @param InstitutionRole $role
     * @param InstitutionAuthorization[] $institutionAuthorizations
     */
    private function addNewAuthorizations(EntityManager $entityManager, InstitutionRole $role, array $institutionAuthorizations)
    {
        foreach ($institutionAuthorizations as $institutionAuthorization) {
            if ($institutionAuthorization->institutionRole === $role) {
                $entityManager->persist($institutionAuthorization);
            }
        }
    }
}
