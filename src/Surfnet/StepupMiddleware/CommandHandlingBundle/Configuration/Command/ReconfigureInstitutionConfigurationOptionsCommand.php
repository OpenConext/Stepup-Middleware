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
    public $institution;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public $useRaLocationsOption;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public $showRaaContactInformationOption;

    /**
     * @var bool
     */
    #[Assert\Type(type: 'boolean')]
    public $verifyEmailOption;


    /**
     * @var int
     */
    #[Assert\Type(type: 'integer')]
    public $numberOfTokensPerIdentityOption;

    #[Assert\NotNull]
    public $allowedSecondFactors;

    /**
     * @var array|null
     */
    public $useRaOption;

    /**
     * @var array|null
     */
    public $useRaaOption;

    /**
     * @var array|null
     */
    public $selectRaaOption;

    /**
     * @var bool|null
     */
    public $selfVetOption;

    /**
     * @var bool|null
     */
    public $selfAssertedTokensOption;

    /**
     * @var bool|null
     */
    public $ssoOn2faOption;
}
