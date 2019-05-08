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
     * @param InstitutionRole $role
     * @param IdentityId $actorId
     * @return InstitutionCollection
     */
    public function getInstitutionsForRole(InstitutionRole $role, IdentityId $actorId)
    {
        $qb = $this->createQueryBuilder('i')
            ->select("a.institution")
            ->innerJoin(RaListing::class, 'r', Join::WITH, "i.institution = r.raInstitution")
            ->innerJoin(
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
            $this->getAuthorizationRolesForAuthorization($role)
        );
        $qb->setParameter(
            'roles',
            $this->getAuthorizationRolesForRa($role)
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
            ->innerJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "r.institution = a.institution AND a.institutionRole IN (:authorizationRoles)"
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
     * @return InstitutionCollection
     */
    public function getInstitutionsForSelectRaaAsSraa()
    {
        $qb = $this->createQueryBuilder('i')
            ->select("a.institution")
            ->innerJoin(
                InstitutionAuthorization::class,
                'a',
                Join::WITH,
                "i.institution = a.institution AND a.institutionRole IN (:authorizationRoles)"
            )
            ->groupBy("a.institution");

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
     * @param InstitutionRole $role
     * @return array
     */
    private function getAuthorizationRolesForAuthorization(InstitutionRole $role)
    {
        switch (true) {
            case $role->equals(InstitutionRole::useRa()):
                return ['use_ra'];
            case $role->equals(InstitutionRole::useRaa()):
                return ['use_raa'];
            default:
                return [];
        }
    }

    /**
     * @param InstitutionRole $role
     * @return array
     */
    private function getAuthorizationRolesForRa(InstitutionRole $role)
    {
        switch (true) {
            case $role->equals(InstitutionRole::useRa()):
                return ['ra', 'raa'];
            case $role->equals(InstitutionRole::useRaa()):
                return ['raa'];
            default:
                return [];
        }
    }
}
