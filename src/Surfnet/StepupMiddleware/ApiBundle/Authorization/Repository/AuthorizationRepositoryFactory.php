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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;

class AuthorizationRepositoryFactory
{
    /**
     * @var InstitutionAuthorizationRepositoryFilter
     */
    private $filter;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * AuthorizationRepositoryFactory constructor.
     * @param EntityManagerInterface $entityManager
     * @param InstitutionAuthorizationRepositoryFilter $filter
     */
    public function __construct(EntityManagerInterface $entityManager, InstitutionAuthorizationRepositoryFilter $filter)
    {
        $this->entityManager = $entityManager;
        $this->filter = $filter;
    }

    /**
     * @param string $entityName
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($entityName)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata = $this->entityManager->getClassMetadata($entityName);
        $repositoryClassName = $metadata->customRepositoryClassName
            ?: $this->entityManager->getConfiguration()->getDefaultRepositoryClassName();

        return new $repositoryClassName($this->entityManager, $metadata, $this->filter);
    }
}
