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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

/**
 * @extends ServiceEntityRepository<InstitutionAuthorization>
 */
class InstitutionAuthorizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionAuthorization::class);
    }

    /**
     * @return InstitutionAuthorization[]
     */
    public function findAuthorizationOptionsForInstitutionByRole(Institution $institution, InstitutionRole $role): array
    {
        return $this->createQueryBuilder('ia')
            ->where('ia.institution = :institution')
            ->andWhere('ia.role = :role')
            ->setParameter('institution', $institution->getInstitution())
            ->setParameter('institutionRole', $role)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstitutionAuthorization[]
     */
    public function findAuthorizationOptionsForInstitution(Institution $institution): array
    {
        return $this->createQueryBuilder('ia')
            ->where('ia.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstitutionAuthorization[]
     */
    public function findSelectRaasForInstitution(Institution $institution): array
    {
        return $this->createQueryBuilder('ia')
            ->where('ia.institutionRelation = :institution')
            ->andWhere('ia.institutionRole = :role')
            ->setParameter('institution', $institution->getInstitution())
            ->setParameter('role', InstitutionRole::selectRaa()->getType())
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws OptimisticLockException
     */
    public function saveInstitutionOption(
        Institution $institution,
        InstitutionAuthorizationOption $institutionOption,
    ): void {
        $institutionAuthorizations = [];

        foreach ($institutionOption->getInstitutions($institution) as $relatedInstitution) {
            $institutionAuthorizations[] = InstitutionAuthorization::create(
                $institution,
                $relatedInstitution,
                $institutionOption->getInstitutionRole(),
            );
        }

        $this->save($institution, $institutionOption->getInstitutionRole(), $institutionAuthorizations);
    }

    public function clearInstitutionOption(Institution $institution): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->createQuery(
            'DELETE ' . InstitutionAuthorization::class . ' ia
            WHERE ia.institution = :institution',
        )
            ->setParameter('institution', $institution->getInstitution())
            ->execute();

        $entityManager->flush();
    }


    /**
     * @throws OptimisticLockException
     */
    public function setDefaultInstitutionOption(Institution $institution): void
    {
        $this->saveInstitutionOption(
            $institution,
            InstitutionAuthorizationOption::getDefault(InstitutionRole::useRa()),
        );
        $this->saveInstitutionOption(
            $institution,
            InstitutionAuthorizationOption::getDefault(InstitutionRole::useRaa()),
        );
        $this->saveInstitutionOption(
            $institution,
            InstitutionAuthorizationOption::getDefault(InstitutionRole::selectRaa()),
        );
    }

    /**
     * @param InstitutionAuthorization[] $institutionAuthorizations
     */
    private function save(Institution $institution, InstitutionRole $role, array $institutionAuthorizations): void
    {
        $entityManager = $this->getEntityManager();

        $this->clearOldAuthorizations($entityManager, $institution, $role);
        $this->addNewAuthorizations($entityManager, $role, $institutionAuthorizations);

        $entityManager->flush();
    }

    private function clearOldAuthorizations(
        EntityManagerInterface $entityManager,
        Institution $institution,
        InstitutionRole $role,
    ): void {
        $entityManager->createQuery(
            'DELETE ' . InstitutionAuthorization::class . ' ia
            WHERE ia.institutionRole = :role AND ia.institution = :institution',
        )
            ->setParameter('role', $role)
            ->setParameter('institution', $institution->getInstitution())
            ->execute();

        $this->getEntityManager()->clear();
    }

    /**
     * @param InstitutionAuthorization[] $institutionAuthorizations
     */
    private function addNewAuthorizations(
        EntityManagerInterface $entityManager,
        InstitutionRole $role,
        array $institutionAuthorizations,
    ): void {
        foreach ($institutionAuthorizations as $institutionAuthorization) {
            if ($institutionAuthorization->institutionRole === $role) {
                $entityManager->persist($institutionAuthorization);
            }
        }
    }
}
