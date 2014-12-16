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

/**
 * May be executed by a Registration Authority to revoke a Registrant's verified or vetted second factor.
 */
class RevokeRegistrantsSecondFactorCommand extends AbstractCommand
{
    /**
     * The ID of the identity that has the authority to issue the revocation of a registrant's second factor.
     *
     * @Assert\NotBlank(message="stepup.command.shared.authority_id.must_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.shared.authority_id.must_be_string")
     *
     * @var string
     */
    public $authorityId;

    /**
     * The ID of an identity.
     *
     * @Assert\NotBlank(message="stepup.command.shared.identity_id.must_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.shared.identity_id.must_be_string")
     *
     * @var string
     */
    public $identityId;

    /**
     * The ID of a verified or vetted second factor.
     *
     * @Assert\NotBlank(message="stepup.command.revoke_second_factor.second_factor_id.must_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.revoke_second_factor.second_factor_id.must_be_string")
     *
     * @var string
     */
    public $secondFactorId;
}
