<?php

/**
 * Copyright 2022 SURFnet bv
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
use Surfnet\Stepup\Identity\Collection\VettingTypeHintCollection;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettingTypeHintRepository;

#[ORM\Entity(repositoryClass: VettingTypeHintRepository::class)]
class VettingTypeHint implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $institution;

    #[ORM\Column(type: 'stepup_vetting_type_hints')]
    public VettingTypeHintCollection $hints;

    public function jsonSerialize()
    {
        return [
            'institution' => $this->institution,
            'hints' => $this->hints,
        ];
    }
}
