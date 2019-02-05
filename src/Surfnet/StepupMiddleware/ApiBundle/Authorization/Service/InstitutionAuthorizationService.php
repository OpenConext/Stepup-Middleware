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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Service;

use Doctrine\ORM\Query\Expr\Join;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\InstitutionListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;

/**
 * Creates InstitutionAuthorizationContext
 *
 * The Context is enriched with the 'isSraa' setting. It verifies if the
 * actor id matches that of one of the SRAA's.
 */
class InstitutionAuthorizationService
{
    /**
     * @var SraaService
     */
    private $sraaService;

    /**
     * @var IdentityService
     */
    private $identityService;
    /**
     * @var InstitutionListingRepository
     */
    private $institutionListingRepository;

    public function __construct(
        SraaService $sraaService,
        IdentityService $identityService,
        InstitutionListingRepository $institutionListingRepository
    ) {
        $this->sraaService = $sraaService;
        $this->identityService = $identityService;
        $this->institutionListingRepository = $institutionListingRepository;
    }

    /**
     * Build the InstitutionAuthorizationContext for use in queries
     *
     * The additional test is performed to indicate if the actor is SRAA.
     *
     * @param Institution $actorInstitution
     * @param InstitutionRoleSet $roleRequirements
     * @param IdentityId $actorId
     * @return InstitutionAuthorizationContext
     */
    public function buildInstitutionAuthorizationContext(Institution $actorInstitution, InstitutionRoleSet $roleRequirements, IdentityId $actorId)
    {
        $identity = $this->identityService->find((string) $actorId);

        if (!$identity) {
            throw new InvalidArgumentException('The provided id is not associated with any known identity');
        }

        $sraa = $this->sraaService->findByNameId($identity->nameId);
        $isSraa = !is_null($sraa);

        $institutions = $this->getInstitutions($roleRequirements, $actorId);

        return new InstitutionAuthorizationContext($actorInstitution, $roleRequirements, $institutions, $isSraa);
    }

    /**
     * @param InstitutionRoleSet $roleRequirements
     * @param IdentityId $actorId
     * @return InstitutionCollection
     */
    private function getInstitutions(InstitutionRoleSet $roleRequirements, IdentityId $actorId) {
        $qb = $this->institutionListingRepository->createQueryBuilder('i')
            ->select("a.institution")
            ->innerJoin(RaListing::class, 'r', Join::WITH, "i.institution = r.raInstitution")
            ->leftJoin(InstitutionAuthorization::class, 'a', Join::WITH, "i.institution = a.institutionRelation AND a.institutionRole IN (:authorizationRoles)")
            ->where("r.identityId = :identityId AND r.role IN(:roles)")
            ->groupBy("a.institution");


        $qb->setParameter('identityId', (string)$actorId);
        $qb->setParameter('authorizationRoles', $this->getAuthorizationRoles($roleRequirements, [InstitutionRole::ROLE_USE_RA => InstitutionRole::ROLE_USE_RA, InstitutionRole::ROLE_USE_RAA => InstitutionRole::ROLE_USE_RAA]));
        $qb->setParameter('roles', $this->getAuthorizationRoles($roleRequirements, [InstitutionRole::ROLE_USE_RA => 'ra', InstitutionRole::ROLE_USE_RAA => 'raa']));

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
