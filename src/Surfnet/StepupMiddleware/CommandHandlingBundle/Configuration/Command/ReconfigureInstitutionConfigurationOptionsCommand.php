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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\ManagementExecutable;
use Symfony\Component\Validator\Constraints as Assert;

final class ReconfigureInstitutionConfigurationOptionsCommand extends AbstractCommand implements ManagementExecutable
{
    /**
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $institution;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public bool $useRaLocationsOption;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public bool $showRaaContactInformationOption;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public bool $verifyEmailOption;


    /**
     * @var int
     */
    #[Assert\Type(type: 'integer')]
    public int $numberOfTokensPerIdentityOption;

    #[Assert\NotNull]
    public array $allowedSecondFactors;

    /**
     * @var array|null
     */
    public ?array $useRaOption = null;

    /**
     * @var array|null
     */
    public ?array $useRaaOption = null;

    /**
     * @var array|null
     */
    public ?array $selectRaaOption = null;

    /**
     * @var bool|null
     */
    public ?bool $selfVetOption = null;

    /**
     * @var bool|null
     */
    public ?bool $selfAssertedTokensOption = null;

    /**
     * @var bool|null
     */
    public ?bool $ssoOn2faOption = null;

    /**
     * @var bool|null
     */
    public ?bool $ssoRegistrationBypassOption = null;
}
