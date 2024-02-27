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
use Doctrine\ORM\Query\Expr\Join;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthorizationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($registry, AuditLogEntry::class);
    }

    /**
     * Return all institutions were the actor has the specified role for
     * The returned institutions are used to filter query results on
     *
     * @return InstitutionCollection
     */
    public function getInstitutionsForRole(RegistrationAuthorityRole $role, IdentityId $actorId): InstitutionCollection
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
                "i.institution = a.institutionRelation AND a.institutionRole IN (:authorizationRoles)",
            )
            ->where("r.identityId = :identityId AND r.role IN(:roles)")
            ->groupBy("a.institution");

        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter(
            'authorizationRoles',
            $this->getAllowedInstitutionRoles($role),
        );
        $identityRoles = $this->getAllowedIdentityRoles($role);
        $qb->setParameter(
            'roles',
            $identityRoles,
        );

        $institutions = $qb->getQuery()->getArrayResult();
        foreach ($institutions as $institution) {
            $this->logger->notice(
                sprintf('Adding %s to authorized institutions', $institution['institution']),
            );
            $result->add(new Institution((string)$institution['institution']));
        }

        // Also get the institutions that are linked to the user via the 'institution_relation' field.
        // Effectively getting the use_raa relation.
        // See https://www.pivotaltracker.com/story/show/181537313
        $qb = $this->_em->createQueryBuilder()
            ->select('ia.institution')
            ->from(InstitutionAuthorization::class, 'ia')
            // Filter the RA listing on the authorizations that apply for the RA(A) listed there
            // For example, when testing a USE_RA institution authorization, the listed RA should have
            // at least a RA or RAA role
            ->join(
                RaListing::class,
                'r',
                Join::WITH,
                'r.raInstitution = ia.institutionRelation AND r.role IN (:identityRoles)',
            )
            ->where('r.identityId = :identityId')
            ->andWhere("ia.institutionRole = :role") // Only filter on use_ra and use_raa roles here.
            ->groupBy('ia.institution');

        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter('role', $this->getInstitutionRoleByRaRole($role));
        $qb->setParameter('identityRoles', $identityRoles);

        $institutions = $qb->getQuery()->getArrayResult();
        foreach ($institutions as $institution) {
            $institutionVo = new Institution((string)$institution['institution']);
            if (!$result->contains($institutionVo)) {
                $result->add($institutionVo);
                $this->logger->notice(
                    sprintf(
                        'Adding %s to authorized institutions from %s',
                        $role->getType(),
                        $institution['institution'],
                    ),
                );
            }
        }

        return $result;
    }

    /**
     * Finds the institutions that have the Select RAA authorization based on
     * the institution of the specified identity.
     */
    public function getInstitutionsForSelectRaaRole(IdentityId $actorId): InstitutionCollection
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
            AuthorityRole::ROLE_RAA,
        );
        // Filter on the SELECT_RAA authorization in the institution authorization projection
        $qb->setParameter(
            'institutionRole',
            InstitutionRole::ROLE_SELECT_RAA,
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
     * - if the required role is RA we should look if the configured institution has USE_RA role
     * - if the required role is RAA we should look if the configured institution has USE_RAA role
     */
    private function getAllowedInstitutionRoles(RegistrationAuthorityRole $role): array
    {
        return match (true) {
            $role->equals(RegistrationAuthorityRole::ra()) => [InstitutionRole::ROLE_USE_RA],
            $role->equals(RegistrationAuthorityRole::raa()) => [InstitutionRole::ROLE_USE_RAA],
            default => [],
        };
    }

    /**
     * This is the mapping to look up allowed identity roles for a specific institution role
     * - if the required role is RA we should look if the identity has a RA or RAA role
     * - if the required role is RAA we should look if the identity has a RAA role
     *
     */
    private function getAllowedIdentityRoles(RegistrationAuthorityRole $role): array
    {
        return match (true) {
            $role->equals(RegistrationAuthorityRole::ra()) => [AuthorityRole::ROLE_RA, AuthorityRole::ROLE_RAA],
            $role->equals(RegistrationAuthorityRole::raa()) => [AuthorityRole::ROLE_RAA],
            default => [],
        };
    }

    private function getInstitutionRoleByRaRole(RegistrationAuthorityRole $role): string
    {
        if ($role->equals(RegistrationAuthorityRole::ra())) {
            return AuthorityRole::ROLE_RA;
        }
        if ($role->equals(RegistrationAuthorityRole::raa())) {
            return AuthorityRole::ROLE_RAA;
        }
    }
}
