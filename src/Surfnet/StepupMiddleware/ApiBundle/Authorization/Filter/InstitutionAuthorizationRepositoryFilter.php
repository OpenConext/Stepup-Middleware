<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

class InstitutionAuthorizationRepositoryFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @param $groupBy
     * @param string $institutionField
     * @param string $authorizationAlias
     */
    public function filter(
        QueryBuilder $queryBuilder,
        InstitutionAuthorizationContextInterface $authorizationContext,
        $groupBy,
        $institutionField,
        $authorizationAlias
    ) {
        $condition = sprintf(
            '(%s AND (%s))',
            $this->getInstitutionDql($authorizationAlias, $institutionField),
            $this->getRolesDql($authorizationAlias, $authorizationContext->getRoleRequirements())
        );

        $queryBuilder->andWhere("{$authorizationAlias}.institutionRelation = :{$this->getInstitutionParameterName($authorizationAlias)}");
        $queryBuilder->innerJoin(InstitutionAuthorization::class, $authorizationAlias, Join::WITH, $condition);
        if (!is_array($groupBy)) {
            $queryBuilder->groupBy($groupBy);
        } else {
            foreach ($groupBy as $by) {
                $queryBuilder->addGroupBy($by);
            }
        }

        $queryBuilder->setParameter($this->getInstitutionParameterName($authorizationAlias), (string)$authorizationContext->getActorInstitution());
    }

    /**
     * @param string $authorizationAlias
     * @param InstitutionRoleSet $roleSet
     * @return string
     */
    private function getRolesDql($authorizationAlias, InstitutionRoleSet $roleSet)
    {
        $keys = array_map(
            function (InstitutionRole $role) use ($authorizationAlias) {
                return $authorizationAlias.".institutionRole = '{$role->getType()}'";
            },
            $roleSet->getRoles()
        );
        return implode(' OR ', $keys);
    }

    /**
     * @param string $authorizationAlias
     * @param string $institutionField
     * @return string
     */
    private function getInstitutionDql($authorizationAlias, $institutionField)
    {
        return sprintf('%s.institution = %s', $authorizationAlias, $institutionField);
    }

    /**
     * @param $authorizationAlias
     * @return string
     */
    private function getInstitutionParameterName($authorizationAlias)
    {
        return "{$authorizationAlias}_institution";
    }
}
