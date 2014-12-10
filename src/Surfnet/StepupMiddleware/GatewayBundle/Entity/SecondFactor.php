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

namespace Surfnet\StepupMiddleware\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_secondfactor_nameid", columns={"name_id"}),
 *      }
 * )
 */
class SecondFactor
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    private $identityId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    private $nameId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    private $institution;

    /**
     * @var string
     *
     * @ORM\Column(length=36)
     */
    private $secondFactorId;

    /**
     * @var string
     *
     * @ORM\Column(length=50)
     */
    private $secondFactorType;

    /**
     * @var string
     *
     * @ORM\Column(length=100)
     */
    private $secondFactorIdentifier;

    public function __construct($identityId, $nameId, $institution, $secondFactorId, $secondFactorIdentifier, $secondFactorType)
    {
        $this->identityId             = $identityId;
        $this->nameId                 = $nameId;
        $this->institution            = $institution;
        $this->secondFactorId         = $secondFactorId;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->secondFactorType       = $secondFactorType;
    }
}
