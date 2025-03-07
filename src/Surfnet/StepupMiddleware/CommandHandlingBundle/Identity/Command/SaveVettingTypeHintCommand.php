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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Vetting type hints are used by Institutions to inform Identities on
 * what vetting type is preferred. Or to explain what effect certain vetting
 * types have on the level of assurance of a second factor token
 *
 * Saving a vetting type hint is performed by a RA. An institution, identity
 * and a set of translated hints are stored in the VettingTypeHintsSavedEvent
 */
class SaveVettingTypeHintCommand extends AbstractCommand implements RaExecutable
{
    /**
     * The ID of an existing identity.
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $identityId;

    /**
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $institution;

    #[Assert\Type(type: 'array')]
    #[Assert\All([
        new Assert\Type("string"),
    ])]
    public array $hints;

    public function getRaInstitution(): ?string
    {
        return null;
    }
}
