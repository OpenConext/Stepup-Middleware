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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\InstitutionListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;

class InstitutionListingRepository extends EntityRepository
{
    public function save(InstitutionListing $institution)
    {
        $this->getEntityManager()->persist($institution);
        $this->getEntityManager()->flush();
    }

    public function addIfNotExists(Institution $institution)
    {
        $existsQuery = $this->createQueryBuilder('i')
            ->where('i.institution = :institution')
            ->setParameter('institution', (string) $institution)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existsQuery) {
            return;
        }

        $listing = InstitutionListing::createFrom($institution);

        $this->save($listing);
    }

    /**
     * @param InstitutionRoleSet $roleRequirements
     * @param IdentityId $actorId
     * @return InstitutionCollection
     */
    public function getInstitutionsForRaa(InstitutionRoleSet $roleRequirements, IdentityId $actorId)
    {
        $qb = $this->createQueryBuilder('i')
            ->select("a.institution")
            ->innerJoin(RaListing::class, 'r', Join::WITH, "i.institution = r.raInstitution")
            ->leftJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "i.institution = a.institutionRelation AND a.institutionRole IN (:authorizationRoles)"
            )
            ->where("r.identityId = :identityId AND r.role IN(:roles)")
            ->groupBy("a.institution");

        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter(
            'authorizationRoles',
            $this->getAuthorizationRoles(
                $roleRequirements,
                [InstitutionRole::ROLE_USE_RA => InstitutionRole::ROLE_USE_RA, InstitutionRole::ROLE_USE_RAA => InstitutionRole::ROLE_USE_RAA]
            )
        );
        $qb->setParameter(
            'roles',
            $this->getAuthorizationRoles(
                $roleRequirements,
                [InstitutionRole::ROLE_USE_RA => 'ra', InstitutionRole::ROLE_USE_RAA => 'raa']
            )
        );

        $institutions = $qb->getQuery()->getArrayResult();

        $result = new InstitutionCollection();
        foreach ($institutions as $institution) {
            $result->add(new Institution((string)$institution['institution']));
        }

        return $result;
    }

    /**
     * @param IdentityId $actorId
     * @return InstitutionCollection
     */
    public function getInstitutionsForSelectRaa(IdentityId $actorId)
    {
        $qb = $this->createQueryBuilder('i')
            ->select("a.institutionRelation")
            ->innerJoin(RaListing::class, 'r', Join::WITH, "i.institution = r.raInstitution")
            ->leftJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "i.institution = a.institution AND a.institutionRole IN (:authorizationRoles)"
            )
            ->where("r.identityId = :identityId AND r.role IN(:roles)")
            ->groupBy("a.institutionRelation");

        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter(
            'authorizationRoles',
            [InstitutionRole::ROLE_SELECT_RAA]
        );
        $qb->setParameter(
            'roles',
            ['raa']
        );
        $institutions = $qb->getQuery()->getArrayResult();

        $result = new InstitutionCollection();
        foreach ($institutions as $institution) {
            $result->add(new Institution((string)$institution['institutionRelation']));
        }

        return $result;
    }


    /**
     * @param Institution $institution
     * @return InstitutionCollection
     */
    public function getInstitutionsForSelectRaaAsSraa(Institution $institution)
    {
        $qb = $this->createQueryBuilder('i')
            ->select("a.institution")
            ->innerJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "i.institution = a.institution AND a.institutionRole IN (:authorizationRoles)"
            )
            ->where("a.institution = :institution")
            ->groupBy("a.institution");

        $qb->setParameter('institution', (string)$institution);
        $qb->setParameter(
            'authorizationRoles',
            [InstitutionRole::ROLE_SELECT_RAA]
        );

        $institutions = $qb->getQuery()->getArrayResult();

        $result = new InstitutionCollection();
        foreach ($institutions as $institution) {
            $result->add(new Institution((string)$institution['institution']));
        }

        return $result;
    }

    /**
     * @param InstitutionRoleSet $roleRequirements
     * @param array $map
     * @return array
     */
    private function getAuthorizationRoles(InstitutionRoleSet $roleRequirements, array $map)
    {
        $result = [];
        foreach ($roleRequirements->getRoles() as $role) {
            $result[] = $map[(string)$role];
        }
        return $result;
    }
}
