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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

class InstitutionAuthorizationRepositoryFilter
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @param string $institutionField
     * @param string $authorizationAlias
     */
    public function filter(
        QueryBuilder $queryBuilder,
        InstitutionAuthorizationContextInterface $authorizationContext,
        $institutionField,
        $authorizationAlias
    ) {
        $values = [];
        foreach ($authorizationContext->getInstitutions() as $institution) {
            $values[] = (string)$institution;
        }

        $parameter = $this->getParameterName($authorizationAlias, 'institutions');

        $whereCondition = sprintf(
            '%s IN (:%s)',
            $institutionField,
            $parameter
        );

        $queryBuilder->andWhere($whereCondition);
        $queryBuilder->setParameter($parameter, $values);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @param $groupBy
     * @param string $institutionField
     * @param string $authorizationAlias
     */
    public function filterListing(
        QueryBuilder $queryBuilder,
        InstitutionAuthorizationContextInterface $authorizationContext,
        $groupBy,
        $institutionField,
        $authorizationAlias
    ) {
        $condition = sprintf(
            '(%s AND (%s))',
            $this->getInstitutionRelationDql($authorizationAlias, $institutionField),
            $this->getRolesDql($authorizationAlias, $authorizationContext->getRoleRequirements())
        );

        $queryBuilder->andWhere("{$authorizationAlias}.institution = :{$this->getParameterName($authorizationAlias, 'institution')}");
        $queryBuilder->innerJoin(InstitutionAuthorization::class, $authorizationAlias, Join::WITH, $condition);
        if (!is_array($groupBy)) {
            $queryBuilder->groupBy($groupBy);
        } else {
            foreach ($groupBy as $by) {
                $queryBuilder->addGroupBy($by);
            }
        }

        $queryBuilder->setParameter($this->getParameterName($authorizationAlias, 'institution'), (string)$authorizationContext->getActorInstitution());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Institution $institution
     * @param $groupBy
     * @param string $institutionField
     * @param string $authorizationAlias
     */
    public function filterCandidate(
        QueryBuilder $queryBuilder,
        Institution $institution,
        $groupBy,
        $institutionField,
        $authorizationAlias
    ) {
        $condition = sprintf(
            '(%s AND (%s))',
            $this->getInstitutionRelationDql($authorizationAlias, $institutionField),
            $this->getRolesDql($authorizationAlias, new InstitutionRoleSet([InstitutionRole::selectRaa()]))
        );

        $queryBuilder->andWhere("{$authorizationAlias}.institution = :{$this->getParameterName($authorizationAlias, 'institution')}");
        $queryBuilder->innerJoin(InstitutionAuthorization::class, $authorizationAlias, Join::WITH, $condition);
        if (!is_array($groupBy)) {
            $queryBuilder->groupBy($groupBy);
        } else {
            foreach ($groupBy as $by) {
                $queryBuilder->addGroupBy($by);
            }
        }

        $queryBuilder->setParameter($this->getParameterName($authorizationAlias, 'institution'), (string)$institution);
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
    private function getInstitutionRelationDql($authorizationAlias, $institutionField)
    {
        return sprintf('%s.institutionRelation = %s', $authorizationAlias, $institutionField);
    }

    /**
     * @param $authorizationAlias
     * @param $name
     * @return string
     */
    private function getParameterName($authorizationAlias, $name)
    {
        return "{$authorizationAlias}_{$name}";
    }
}
