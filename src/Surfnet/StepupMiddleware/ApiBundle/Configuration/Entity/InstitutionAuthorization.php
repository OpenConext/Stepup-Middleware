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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;

#[ORM\Table]
#[ORM\Index(name: 'idx_authorization', columns: ['institution', 'institution_relation', 'institution_role'])]
#[ORM\Entity(repositoryClass: InstitutionAuthorizationRepository::class)]
class InstitutionAuthorization
{
    /**
     *
     * @var Institution
     */
    #[ORM\Id]
    #[ORM\Column(type: 'stepup_configuration_institution')]
    public $institution;

    /**
     *
     * @var Institution
     */
    #[ORM\Id]
    #[ORM\Column(type: 'stepup_configuration_institution')]
    public $institutionRelation;

    /**
     *
     * @var InstitutionRole
     */
    #[ORM\Id]
    #[ORM\Column(type: 'stepup_institution_role', length: 10)]
    public $institutionRole;

    /**
     * @param Institution $institution
     * @param Institution $institutionRelation
     * @param InstitutionRole $institutionRole
     * @return InstitutionAuthorization
     */
    public static function create(
        Institution $institution,
        Institution $institutionRelation,
        InstitutionRole $institutionRole
    ) {
        $options = new self;

        $options->institution = $institution;
        $options->institutionRelation = $institutionRelation;
        $options->institutionRole = $institutionRole;

        return $options;
    }
}
