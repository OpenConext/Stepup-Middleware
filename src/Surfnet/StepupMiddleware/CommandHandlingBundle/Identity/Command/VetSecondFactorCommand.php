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

class VetSecondFactorCommand extends AbstractCommand
{
    /**
     * The ID of an existing identity.
     *
     * @Assert\NotBlank(message="stepup.command.vet_second_factor.identity_id.must_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.vet_second_factor.identity_id.must_be_string")
     *
     * @var string
     */
    public $identityId;

    /**
     * @Assert\NotBlank(message="stepup.command.vet_second_factor.registration_code.may_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.vet_second_factor.registration_code.must_be_string")
     *
     * @var string
     */
    public $registrationCode;

    /**
     * @Assert\NotBlank(message="stepup.command.vet_second_factor.second_factor_identifier.may_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.vet_second_factor.second_factor_identifier.must_be_string")
     *
     * @var string
     */
    public $secondFactorIdentifier;

    /**
     * @Assert\NotBlank(message="stepup.command.vet_second_factor.document_number.may_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.vet_second_factor.document_number.must_be_string")
     *
     * @var string
     */
    public $documentNumber;

    /**
     * @Assert\EqualTo(value=true, message="stepup.command.vet_second_factor.identity_verified.must_be_true")
     *
     * @var boolean
     */
    public $identityVerified;
}
