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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(
 *     repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRevocationRepository"
 * )
 * @ORM\Table(
 *      name="second_factor_revocation",
 *      indexes={
 *          @ORM\Index(name="idx_secondfactorrevocation_recordedon", columns={"recorded_on"})
 *      }
 * )
 */
class SecondFactorRevocation
{
    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\Column(type="institution")
     *
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $institution;

    /**
     * @ORM\Column(length=36, nullable=true)
     *
     * @var string|null
     */
    public $secondFactorType;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $revokedBy;

    /**
     * @ORM\Column(type="stepup_utc_datetime")
     *
     * @var \Surfnet\Stepup\DateTime\UtcDateTime
     */
    public $recordedOn;
}
