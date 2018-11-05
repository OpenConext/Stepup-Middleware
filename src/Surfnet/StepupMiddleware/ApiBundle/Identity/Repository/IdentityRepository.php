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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;

class IdentityRepository extends EntityRepository
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
     * @param string $id
     * @return Identity|null
     */
    public function find($id)
    {
        /** @var Identity|null $identity */
        $identity = parent::find($id);

        return $identity;
    }

    /**
     * @param Identity $identity
     */
    public function save(Identity $identity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($identity);
        $entityManager->flush();
    }

    /**
     * @param IdentityQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(
        IdentityQuery $query
    ) {
        $queryBuilder = $this->createQueryBuilder('i');

        // If no institution context is provided, we are not able to query with authorization context
        if ($query->authorizationContext) {
            // Modify query to filter on authorization
            $this->authorizationRepositoryFilter->filter($queryBuilder, $query->authorizationContext, 'i.id', 'i.institution', 'iac');
        }

        if ($query->institution) {
            $queryBuilder
                ->andWhere('i.institution = :institution')
                ->setParameter('institution', $query->institution);
        }

        if ($query->nameId) {
            $queryBuilder
                ->andWhere('i.nameId = :nameId')
                ->setParameter('nameId', $query->nameId);
        }

        if ($query->email) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.email, :email) > 0')
                ->setParameter('email', $query->email);
        }

        if ($query->commonName) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(i.commonName, :commonName) > 0')
                ->setParameter('commonName', $query->commonName);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string[] $nameIds
     * @return Identity[] Indexed by NameID.
     */
    public function findByNameIdsIndexed(array $nameIds)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('i')
            ->from('Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity', 'i', 'i.nameId')
            ->where('i.nameId IN (:nameIds)')
            ->setParameter('nameIds', $nameIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param NameId      $nameId
     * @param Institution $institution
     *
     * @return bool
     */
    public function hasIdentityWithNameIdAndInstitution(NameId $nameId, Institution $institution)
    {
        $identityCount = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.nameId = :nameId')
            ->andWhere('i.institution = :institution')
            ->setParameter('nameId', $nameId->getNameId())
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getSingleScalarResult();

        return $identityCount > 0;
    }

    /**
     * @param NameId      $nameId
     * @param Institution $institution
     * @return Identity
     */
    public function findOneByNameIdAndInstitution(NameId $nameId, Institution $institution)
    {
        return $this->createQueryBuilder('i')
                ->where('i.nameId = :nameId')
                ->setParameter('nameId', $nameId->getNameId())
                ->andWhere('i.institution = :institution')
                ->setParameter('institution', $institution->getInstitution())
                ->getQuery()
                ->getSingleResult();
    }

    public function removeByIdentityId(IdentityId $identityId)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'i')
            ->where('i.id = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    /**
     * @param Institution $institution
     * @return ArrayCollection|Identity[]
     */
    public function findByInstitution(Institution $institution)
    {
        return $this->createQueryBuilder('i')
            ->where('i.institution = :institution')
            ->setParameter('institution', $institution->getInstitution())
            ->getQuery()
            ->getResult();
    }
}
