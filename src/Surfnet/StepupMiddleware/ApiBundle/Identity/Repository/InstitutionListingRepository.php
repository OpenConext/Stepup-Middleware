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
use Doctrine\Persistence\ManagerRegistry;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\InstitutionListing;

/**
 * @deprecated This could probably be removed and is only used in migrations
 * @see app/DoctrineMigrations/Version20160719090052.php#L51
 * @extends ServiceEntityRepository<InstitutionListing>
 */
class InstitutionListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionListing::class);
    }

    public function save(InstitutionListing $institution): void
    {
        $this->getEntityManager()->persist($institution);
        $this->getEntityManager()->flush();
    }

    public function addIfNotExists(Institution $institution): void
    {
        $existsQuery = $this->createQueryBuilder('i')
            ->where('i.institution = :institution')
            ->setParameter('institution', (string)$institution)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existsQuery) {
            return;
        }

        $listing = InstitutionListing::createFrom($institution);

        $this->save($listing);
    }
}
