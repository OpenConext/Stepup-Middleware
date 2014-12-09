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

namespace Surfnet\Stepup\Identity\Entity;

use Broadway\EventSourcing\EventSourcedEntity;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

/**
 * A second factor whose possession has been proven by the registrant and the registrant's e-mail address has been
 * verified. The registrant must visit a registration authority next.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 */
class VerifiedSecondFactor extends EventSourcedEntity
{
    /**
     * @var SecondFactorId
     */
    private $id;

    /**
     * @var Identity
     */
    private $identity;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $secondFactorIdentifier;

    /**
     * @var DateTime
     */
    private $registrationRequestedAt;

    /**
     * @var string
     */
    private $registrationCode;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param string $type
     * @param string $secondFactorIdentifier
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     * @return self
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        $type,
        $secondFactorIdentifier,
        DateTime $registrationRequestedAt,
        $registrationCode
    ) {
        if (!is_string($registrationCode)) {
            throw InvalidArgumentException::invalidType('string', 'registrationCode', $registrationCode);
        }

        $secondFactor = new self;
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->registrationRequestedAt = $registrationRequestedAt;
        $secondFactor->registrationCode = $registrationCode;

        return $secondFactor;
    }

    final private function __construct()
    {
    }
}
