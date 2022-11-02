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
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $institution;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $useRaLocationsOption;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $showRaaContactInformationOption;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $verifyEmailOption;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $ssoOn2faOption;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $selfVetOption;

    /**
     * @Assert\Type(type="boolean")
     *
     * @var bool
     */
    public $selfAssertedTokensOption;

    /**
     * @Assert\Type(type="integer")
     *
     * @var int
     */
    public $numberOfTokensPerIdentityOption;

    /**
     * @Assert\NotNull()
     */
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
}
