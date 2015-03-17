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
use Symfony\Component\Validator\Constraints as Assert;

final class BootstrapIdentityWithYubikeySecondFactorCommand extends AbstractCommand
{
    /**
     * @Assert\NotBlank(message="Identity ID may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $identityId;

    /**
     * @Assert\NotBlank(message="NameID may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $nameId;

    /**
     * @Assert\NotBlank(message="Institution may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $institution;

    /**
     * @Assert\NotBlank(message="Common name may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $commonName;

    /**
     * @Assert\NotBlank(message="E-mail may not be blank")
     * @Assert\Email(message="E-mail must be a valid e-mail address")
     *
     * @var string
     */
    public $email;

    /**
     * @Assert\NotBlank(message="Second factor ID may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $secondFactorId;

    /**
     * @Assert\NotBlank(message="Yubikey may not be blank")
     * @Assert\Type("string", message="Common name must be a string")
     *
     * @var string
     */
    public $yubikeyPublicId;
}
