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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query\Expr\Join;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaCandidateRepository extends EntityRepository
{
    /**
     * @var InstitutionAuthorizationRepositoryFilter
     */
    private $authorizationRepositoryFilter;

    public function __construct(
        EntityManager $em,
        Mapping\ClassMetadata $class,
        InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
    ) {
        parent::__construct($em, $class);
        $this->authorizationRepositoryFilter = $authorizationRepositoryFilter;
    }

    /**
     * @param RaCandidate $raCandidate
     * @return void
     */
    public function merge(RaCandidate $raCandidate)
    {
        $raCandidate = $this->getEntityManager()->merge($raCandidate);
        $this->getEntityManager()->persist($raCandidate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param IdentityId $identityId
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId)
    {
        $raCandidates = $this->findByIdentityId($identityId);

        if (empty($raCandidates)) {
            return;
        }

        foreach ($raCandidates as $candidate) {
            $this->getEntityManager()->remove($candidate);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @param Institution $institution
     * @param InstitutionCollection $raInstitutions
     * @return void
     */
    public function removeInstitutionsNotInList(Institution $institution, InstitutionCollection $raInstitutions)
    {
        $raCandidates = $this->createQueryBuilder('rac')
            ->where('rac.raInstitution = :raInstitution')
            ->andWhere('rac.institution NOT IN (:institutions)')
            ->setParameter('raInstitution', $institution)
            ->setParameter('institutions', $raInstitutions->serialize())
            ->getQuery()
            ->getResult();

        $em = $this->getEntityManager();
        foreach ($raCandidates as $raCandidate) {
            $em->remove($raCandidate);
        }

        $em->flush();
    }

    /**
     * @param Institution $raInstitution
     * @return void
     */
    public function removeByRaInstitution(Institution $raInstitution)
    {
        $raCandidates = $this->findByRaInstitution($raInstitution);

        if (empty($raCandidates)) {
            return;
        }

        $em = $this->getEntityManager();
        foreach ($raCandidates as $raCandidate) {
            $em->remove($raCandidate);
        }

        $em->flush();
    }

    /**
     * @param IdentityId $identityId
     * @param Institution $raInstitution
     * @return void
     */
    public function removeByIdentityIdAndRaInstitution(IdentityId $identityId, Institution $raInstitution)
    {
        $raCandidate = $this->findByIdentityIdAndRaInstitution($identityId, $raInstitution);

        if (!$raCandidate) {
            return;
        }
        $em = $this->getEntityManager();
        $em->remove($raCandidate);
        $em->flush();
    }

    /**
     * @param string[] $nameIds
     * @return void
     */
    public function removeByNameIds($nameIds)
    {
        $raCandidates = $this->findByNameIds($nameIds);

        $em = $this->getEntityManager();
        foreach ($raCandidates as $raCandidate) {
            $em->remove($raCandidate);
        }

        $em->flush();
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(RaCandidateQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rac');

        // Modify query to filter on authorization
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'rac.raInstitution',
            'iac'
        );

        if ($query->institution) {
            $queryBuilder
                ->andWhere('rac.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(rac.commonName, :commonName) > 0')
                ->setParameter('commonName', $query->commonName);
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(rac.email, :email) > 0')
                ->setParameter('email', $query->email);
        }

        if (!empty($query->secondFactorTypes)) {
            $queryBuilder
                ->innerJoin(VettedSecondFactor::class, 'vsf', Join::WITH, 'rac.identityId = vsf.identityId')
                ->andWhere('vsf.type IN (:secondFactorTypes)')
                ->setParameter('secondFactorTypes', $query->secondFactorTypes);
        }

        if (!empty($query->raInstitution)) {
            $queryBuilder
                ->andWhere('rac.raInstitution = :raInstitution')
                ->setParameter('raInstitution', $query->raInstitution);
        }

        $queryBuilder->groupBy('rac.identityId');

        return $queryBuilder->getQuery();
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createOptionsQuery(RaCandidateQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('rac')
            ->select('rac.institution')
            ->groupBy('rac.institution');

        // Modify query to filter on authorization
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $query->authorizationContext,
            'rac.raInstitution',
            'iac'
        );

        return $queryBuilder->getQuery();
    }

    /**
     * @param string[] $sraaList
     * @return RaCandidate[]
     */
    public function findByNameIds(array $sraaList)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.nameId IN (:sraaList)')
            ->setParameter('sraaList', $sraaList)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $identityId
     * @return RaCandidate[]
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByIdentityId($identityId)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.identityId = :identityId')
            ->setParameter('identityId', $identityId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $identityId
     * @param Institution $raInstitution
     * @return null|RaCandidate
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByIdentityIdAndRaInstitution($identityId, Institution $raInstitution)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.identityId = :identityId')
            ->andWhere('rac.raInstitution = :raInstitution')
            ->setParameter('identityId', $identityId)
            ->setParameter('raInstitution', $raInstitution)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $identityId
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @return RaCandidate[]
     */
    public function findAllRaasByIdentityId($identityId, InstitutionAuthorizationContextInterface $authorizationContext)
    {
        $queryBuilder = $this->createQueryBuilder('rac')
            ->where('rac.identityId = :identityId')
            ->setParameter('identityId', $identityId)
            ->orderBy('rac.raInstitution');

        // Modify query to filter on authorization
        $this->authorizationRepositoryFilter->filter(
            $queryBuilder,
            $authorizationContext,
            'rac.raInstitution',
            'iac'
        );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Institution $raInstitution
     * @return ArrayCollection|RaCandidate[]
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByRaInstitution(Institution $raInstitution)
    {
        return $this->createQueryBuilder('rac')
            ->where('rac.raInstitution = :raInstitution')
            ->setParameter('raInstitution', $raInstitution)
            ->getQuery()
            ->getResult();
    }
}
