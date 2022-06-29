<?php

/**
 * Copyright 2022 SURF bv
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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfAsserted;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Register a self-asserted token
 *
 * A self-asserted token (SAT) registration registers a Second Factor token
 * with the addition of passing a Recovery Token id which is used to
 * verify the Identity is in possession of a Recovery Token.
 *
 * Registration of a SAT results in a SecondFactorVettedEvent (or the one
 * without email verification). And sets the VettingType to be 'self-asserted'.
 *
 * Example body of HTTP Post request to Middleware API:
 *
 * ```
 * {
 *      "meta": {
 *          "actor_id": "dea7e7dd-76ac-46f6-9ea9-a616bf8996a8",
 *          "actor_institution": "institution-a.example.com"
 *      },
 *      "command": {
 *          "name":"Identity:RegisterSelfAsseredtSecondFactor",
 *          "uuid":"d12cb984-5720-405a-9533-af2beef78ee2",
 *          "payload":{
 *              "id": "e34cb336-5723-781a-9587-af2beef78aa2",
 *              "identity_id": "dea7e7dd-76ac-46f6-9ea9-a616bf8996a8",
 *              "second_factor_id": "0b88c646-9a73-43ea-a943-502cab15d5c2",
 *              "second_factor_type": "yubikey",
 *              "second_factor_identifier": "02513949",
 *              "authoring_recovery_token_id": "1e598620-6cbb-4d14-b7f5-df0aaba847e5"
 *          }
 *      }
 *  }
 *
 *
 * Note: This is a SelfAsserted command. Marking it a token that is vetted without
 * visiting a physical registration authority. Self-vetted tokens being the
 * other 'self-asserted' token type.
 */
class RegisterSelfAssertedSecondFactorCommand extends AbstractCommand implements SelfServiceExecutable, SelfAsserted
{
    /**
     * The ID of an existing identity.
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $identityId;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $secondFactorId;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $secondFactorType;

    /**
     * The Identifier of the Second Factor Token. Examples:
     * - Yubikey: this would be the public key,
     * - SMS: the phone number
     * - GSSP: The identifier the GSSP released during registration
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $secondFactorIdentifier;

    /**
     * The ID of the Recovery Token used to authenticate the self-asserted token
     * registration with. In case of a first time Topken registration, the Recovery
     * Token was not authenticated. It's proof of possession was already proven by
     * registering it. The Recovery Token id is passed along regardless
     * registration type. And is used to project in the Audit Log what recovery token
     * was used during registration.
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $authoringRecoveryTokenId;

    public function getIdentityId()
    {
        $this->identityId;
    }
}
