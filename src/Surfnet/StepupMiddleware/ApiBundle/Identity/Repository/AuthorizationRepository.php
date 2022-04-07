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
use Doctrine\ORM\Query\Expr\Join;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthorizationRepository extends ServiceEntityRepository
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, AuditLogEntry::class);
        $this->logger = $logger;
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
        $result = new InstitutionCollection();
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
        foreach ($institutions as $institution) {
            $this->logger->notice(
                sprintf('Adding %s to authorized institutions', $institution['institution'])
            );
            $result->add(new Institution((string)$institution['institution']));
        }

        // Also get the institutions that are linked to the user via the 'institution_relation' field.
        // Effectively getting the use_raa relation.
        // See https://www.pivotaltracker.com/story/show/181537313
        $qb = $this->_em->createQueryBuilder()
            ->select('ia.institution')
            ->from(InstitutionAuthorization::class, 'ia')
            ->join(RaListing::class, 'r', Join::WITH, 'r.raInstitution = ia.institutionRelation')
            ->where('r.identityId = :identityId')
            ->andWhere("ia.institutionRole = :role") // Only filter on use_ra and use_raa roles here.
            ->groupBy('ia.institution');

        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter('role', $role->getType());

        $institutions = $qb->getQuery()->getArrayResult();
        foreach ($institutions as $institution) {
            $institutionVo = new Institution((string)$institution['institution']);
            if (!$result->contains($institutionVo)) {
                $result->add($institutionVo);
                $this->logger->notice(
                    sprintf('Adding %s to authorized institutions from use_raa', $institution['institution'])
                );
            }
        }

        return $result;
    }

    /**
     * Finds the institutions that have the Select RAA authorization based on
     * the institution of the specified identity.
     */
    public function getInstitutionsForSelectRaaRole(IdentityId $actorId)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select("ci.institution")
            ->from(InstitutionAuthorization::class, 'ia')
            ->innerJoin(ConfiguredInstitution::class, 'ci', Join::WITH, 'ia.institutionRelation = ci.institution')
            ->innerJoin(Identity::class, 'i', Join::WITH, 'ia.institution = i.institution AND i.id = :identityId')
            ->innerJoin(RaListing::class, 'ra', Join::WITH, 'i.id = ra.identityId AND ra.role = :authorizationRole')
            ->where('ia.institutionRole = :institutionRole AND ra.role = :authorizationRole')
            ->groupBy("ci.institution");

        $qb->setParameter('identityId', (string)$actorId);
        // The identity requires RAA role to perform this search
        $qb->setParameter(
            'authorizationRole',
            AuthorityRole::ROLE_RAA
        );
        // Filter on the SELECT_RAA authorization in the institution authorization projection
        $qb->setParameter(
            'institutionRole',
            InstitutionRole::ROLE_SELECT_RAA
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
