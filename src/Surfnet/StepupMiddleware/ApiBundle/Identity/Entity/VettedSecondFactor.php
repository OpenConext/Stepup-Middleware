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
use JsonSerializable;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettedSecondFactorRepository;

#[ORM\Table]
#[ORM\Index(name: 'idx_vetted_second_factor_type', columns: ['type'])]
#[ORM\Index(name: 'idx_vetted_second_factor_vetting_type', columns: ['vetting_type'])]
#[ORM\Entity(repositoryClass: VettedSecondFactorRepository::class)]
class VettedSecondFactor implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    #[ORM\Column(length: 36)]
    public string $identityId;

    #[ORM\Column(length: 16)]
    public string $type;

    #[ORM\Column(length: 255)]
    public string $secondFactorIdentifier;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $vettingType;

    public function isEqual(VettedSecondFactor $vettedSecondFactor): bool
    {
        return $vettedSecondFactor->type == $this->type && $vettedSecondFactor->secondFactorIdentifier == $this->secondFactorIdentifier;
    }

    public function vettingType(): string
    {
        if (!$this->vettingType) {
            return VettingType::TYPE_ON_PREMISE;
        }
        return $this->vettingType;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'vetting_type' => $this->vettingType,
        ];
    }
}
