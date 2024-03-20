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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Symfony\Component\Validator\Constraints as Assert;

class AmendRegistrationAuthorityInformationCommand extends AbstractCommand implements RaExecutable
{
    /**
     *
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $identityId;

    /**
     *
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $location;

    /**
     *
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $contactInformation;

    /**
     *
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $raInstitution;

    /**
     * @inheritDoc
     */
    public function getRaInstitution(): string
    {
        return $this->raInstitution;
    }
}
