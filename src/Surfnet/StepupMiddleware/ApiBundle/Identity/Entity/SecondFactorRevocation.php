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
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SecondFactorRevocationRepository;

#[ORM\Table(name: 'second_factor_revocation')]
#[ORM\Index(name: 'idx_secondfactorrevocation_recordedon', columns: ['recorded_on'])]
#[ORM\Entity(repositoryClass: SecondFactorRevocationRepository::class)]
class SecondFactorRevocation
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    /**
     * @var Institution
     */
    #[ORM\Column(type: 'institution')]
    public Institution $institution;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 36, nullable: true)]
    public ?string $secondFactorType = null;

    /**
     * @var string
     */
    #[ORM\Column]
    public string $revokedBy;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'stepup_datetime')]
    public DateTime $recordedOn;
}
