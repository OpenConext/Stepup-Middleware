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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\UnverifiedSecondFactorRepository;

#[ORM\Entity(repositoryClass: UnverifiedSecondFactorRepository::class)]
class UnverifiedSecondFactor implements JsonSerializable
{
    /**
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    /**
     * @var string
     */
    #[ORM\Column(length: 36)]
    public string $identityId;

    /**
     * @var string
     */
    #[ORM\Column(length: 16)]
    public string $type;

    /**
     * The second factor identifier, ie. telephone number, Yubikey public ID, Tiqr ID
     * @var string
     */
    #[ORM\Column(length: 255)]
    public string $secondFactorIdentifier;

    /**
     * @var string
     */
    #[ORM\Column(length: 32)]
    public string $verificationNonce;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'second_factor_identifier' => $this->secondFactorIdentifier,
        ];
    }
}
