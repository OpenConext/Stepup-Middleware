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
use Doctrine\ORM\EntityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

class RaListingRepository extends EntityRepository
{
    /**
     * @param string $id The RA's identity id.
     * @return null|RaListing
     */
    public function find($id)
    {
        return parent::find($id);
    }

    public function save(RaListing $raListingEntry)
    {
        $this->getEntityManager()->persist($raListingEntry);
        $this->getEntityManager()->flush();
    }

    /**
     * @param RaListingQuery $query
     * @return \Doctrine\ORM\Query
     */
    public function createSearchQuery(RaListingQuery $query)
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.institution = :institution')
            ->setParameter('institution', $query->institution);

        $orderDirection = $query->orderDirection === 'asc' ? 'ASC' : 'DESC';
        switch ($query->orderBy) {
            case 'commonName':
                $queryBuilder->orderBy('r.commonName', $orderDirection);
                break;
            default:
                throw new RuntimeException(sprintf('Unknown order by column "%s"', $query->orderBy));
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string $institution
     * @return ArrayCollection
     */
    public function getRaasByInstitution($institution)
    {
        $listings = $this->createQueryBuilder('rl')
            ->where('rl.role = :role')
            ->andWhere('rl.institution = :institution')
            ->setParameters([
                'role'        => AuthorityRole::raa(),
                'institution' => $institution
            ])
            ->getQuery()
            ->getResult();

        return new ArrayCollection($listings);
    }

    public function saveAll($listingsToSave)
    {
        $em = $this->getEntityManager();
        foreach ($listingsToSave as $raListing) {
            $em->persist($raListing);
        }

        $em->flush();
    }
}
