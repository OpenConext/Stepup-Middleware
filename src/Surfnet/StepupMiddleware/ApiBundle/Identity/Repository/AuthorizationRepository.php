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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

class AuthorizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogEntry::class);
    }

    /**
     * Return all institutions were the actor has the specified role for
     * The returned institutions are used to filter query results on
     *
     * @param InstitutionRole $role
     * @param IdentityId $actorId
     * @return InstitutionCollection
     */
    public function getInstitutionsForRole(InstitutionRole $role, IdentityId $actorId)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select("a.institution")
            ->from(ConfiguredInstitution::class, 'i')
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
            $this->getAllowedInstitutionRoles($role)
        );
        $qb->setParameter(
            'roles',
            $this->getAllowedIdentityRoles($role)
        );

        $institutions = $qb->getQuery()->getArrayResult();

        $result = new InstitutionCollection();
        foreach ($institutions as $institution) {
            $result->add(new Institution((string)$institution['institution']));
        }

        return $result;
    }

    /**
     * This is the mapping to look up allowed institution roles
     * - if the institution role is RA we should look if the configured institution has RA role
     * - if the institution role is RAA we should look if the configured institution has RAA role
     *
     * @param InstitutionRole $role
     * @return array
     */
    private function getAllowedInstitutionRoles(InstitutionRole $role)
    {
        switch (true) {
            case $role->equals(InstitutionRole::useRa()):
                return [InstitutionRole::ROLE_USE_RA];
            case $role->equals(InstitutionRole::useRaa()):
                return [InstitutionRole::ROLE_USE_RAA];
            default:
                return [];
        }
    }

    /**
     * This is the mapping to look up allowed identity roles for a specific institution role
     * - if the institution role is RA we should look if the identity has a RA or RAA role
     * - if the institution role is RAA we should look if the identity has a RAA role
     *
     * @param InstitutionRole $role
     * @return array
     */
    private function getAllowedIdentityRoles(InstitutionRole $role)
    {
        switch (true) {
            case $role->equals(InstitutionRole::useRa()):
                return [AuthorityRole::ROLE_RA, AuthorityRole::ROLE_RAA];
            case $role->equals(InstitutionRole::useRaa()):
                return [AuthorityRole::ROLE_RAA];
            default:
                return [];
        }
    }
}
