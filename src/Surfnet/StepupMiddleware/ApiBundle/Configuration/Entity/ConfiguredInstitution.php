<?php

/**
 * Copyright 2016 SURFnet B.V.
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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;

#[ORM\Entity(repositoryClass: ConfiguredInstitutionRepository::class)]
class ConfiguredInstitution
{
    /**
     *
     * @var Institution
     */
    #[ORM\Id]
    #[ORM\Column(type: 'stepup_configuration_institution')]
    public $institution;

    /**
     * @param Institution $institution
     * @return ConfiguredInstitution
     */
    public static function createFrom(Institution $institution): self
    {
        $configuredInstitution = new self;
        $configuredInstitution->institution = $institution;

        return $configuredInstitution;
    }

    public function jsonSerialize(): array
    {
        return ['institution' => $this->institution];
    }
}
