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

use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Symfony\Component\Validator\Constraints as Assert;

class ProvePhoneRecoveryTokenPossessionCommand extends AbstractCommand implements SelfServiceExecutable
{
    /**
     * The ID of an existing identity.
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $identityId;

    /**
     * The ID of the recovery code to create.
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $recoveryTokenId;

    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $recoveryTokenType = RecoveryTokenType::TYPE_SMS;

    /**
     * The phone number
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[Assert\Regex(pattern: '~^\+[\d\s]+ \(0\) \d+$~')]
    public string $phoneNumber;

    public function getIdentityId(): string
    {
        return $this->identityId;
    }
}
