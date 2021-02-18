<?php

/**
 * Copyright 2017 SURFnet B.V.
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
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * @ORM\Entity(
 *      repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\AllowedSecondFactorRepository"
 * )
 */
class AllowedSecondFactor
{
    /**
     * @ORM\Id
     * @ORM\Column(type="stepup_configuration_institution")
     *
     * @var Institution
     */
    public $institution;

    /**
     * @ORM\Id
     * @ORM\Column(type="stepup_second_factor_type")
     *
     * @var SecondFactorType
     */
    public $secondFactorType;

    private function __construct()
    {
    }

    /**
     * @param Institution $institution
     * @param SecondFactorType $secondFactorType
     * @return AllowedSecondFactor
     */
    public static function createFrom(Institution $institution, SecondFactorType $secondFactorType)
    {
        $allowedSecondFactor                   = new self;
        $allowedSecondFactor->institution      = $institution;
        $allowedSecondFactor->secondFactorType = $secondFactorType;

        return $allowedSecondFactor;
    }
}
