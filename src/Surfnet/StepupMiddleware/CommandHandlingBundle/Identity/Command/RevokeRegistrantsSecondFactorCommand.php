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

/**
 * May be executed by a Registration Authority to revoke a Registrant's verified or vetted second factor.
 */
class RevokeRegistrantsSecondFactorCommand extends AbstractCommand implements RaExecutable
{
    /**
     * The ID of the identity that has the authority to issue the revocation of a registrant's second factor.
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $authorityId;

    /**
     * The ID of an identity.
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $identityId;

    /**
     * The ID of a verified or vetted second factor.
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $secondFactorId;

    /**
     * @inheritDoc
     */
    public function getRaInstitution(): ?string
    {
        // Returning null as opposed to having the institution on this command was done
        // because the RA (actor) institution can be loaded from the authorityId
        // See: src/Surfnet/StepupMiddleware/ApiBundle/Authorization/Service/CommandAuthorizationService.php:163
        return null;
    }
}
