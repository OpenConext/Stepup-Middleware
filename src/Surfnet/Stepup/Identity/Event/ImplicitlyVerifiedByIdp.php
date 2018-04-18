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

namespace Surfnet\Stepup\Identity\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class ImplicitlyVerifiedByIdp implements Forgettable, SerializableInterface
{
    /**
     * @var IdentityId
     */
    public $identityId;

    /**
     * @var Institution
     */
    public $identityInstitution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    public $secondFactorType;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier
     */
    public $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $registrationRequestedAt;

    /**
     * @var string
     */
    public $registrationCode;

    /**
     * @param IdentityId        $identityId
     * @param Institution       $identityInstitution
     * @param SecondFactorId    $secondFactorId
     * @param SecondFactorType  $secondFactorType
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @param DateTime          $registrationRequestedAt
     * @param string            $registrationCode
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        DateTime $registrationRequestedAt,
        $registrationCode
    ) {
        $this->identityId = $identityId;
        $this->identityInstitution = $identityInstitution;
        $this->secondFactorId          = $secondFactorId;
        $this->secondFactorType        = $secondFactorType;
        $this->secondFactorIdentifier  = $secondFactorIdentifier;
        $this->registrationRequestedAt = $registrationRequestedAt;
        $this->registrationCode        = $registrationCode;
    }

    public static function deserialize(array $data)
    {
        $secondFactorType = new SecondFactorType($data['second_factor_type']);

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            $secondFactorType,
            SecondFactorIdentifierFactory::unknownForType($secondFactorType),
            DateTime::fromString($data['registration_requested_at']),
            $data['registration_code']
        );
    }

    public function serialize()
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'second_factor_type'        => (string) $this->secondFactorType,
            'registration_requested_at' => (string) $this->registrationRequestedAt,
            'registration_code'         => $this->registrationCode,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withSecondFactorIdentifier($this->secondFactorIdentifier, $this->secondFactorType);
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->secondFactorIdentifier = $sensitiveData->getSecondFactorIdentifier();
    }
}
