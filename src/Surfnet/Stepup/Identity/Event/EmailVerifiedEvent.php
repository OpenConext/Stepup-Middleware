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

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;

class EmailVerifiedEvent extends IdentityEvent
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    public $secondFactorType;

    /**
     * @var string
     */
    private $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $registrationRequestedAt;

    /**
     * @var \Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId
     */
    public $identifyingDataId;

    /**
     * @var string
     */
    public $registrationCode;

    /**
     * @var string Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId        $identityId
     * @param Institution       $identityInstitution
     * @param SecondFactorId    $secondFactorId
     * @param SecondFactorType  $secondFactorType
     * @param string            $secondFactorIdentifier
     * @param DateTime          $registrationRequestedAt
     * @param IdentifyingDataId $identifyingDataId
     * @param string            $registrationCode
     * @param string            $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        $secondFactorIdentifier,
        DateTime $registrationRequestedAt,
        IdentifyingDataId $identifyingDataId,
        $registrationCode,
        $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId          = $secondFactorId;
        $this->secondFactorType        = $secondFactorType;
        $this->secondFactorIdentifier  = $secondFactorIdentifier;
        $this->registrationRequestedAt = $registrationRequestedAt;
        $this->identifyingDataId       = $identifyingDataId;
        $this->registrationCode        = $registrationCode;
        $this->preferredLocale         = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = $this->secondFactorType;
        $metadata->secondFactorIdentifier = $this->secondFactorIdentifier;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new SecondFactorType($data['second_factor_type']),
            $data['second_factor_identifier'],
            DateTime::fromString($data['registration_requested_at']),
            new IdentifyingDataId($data['identifying_data_id']),
            $data['registration_code'],
            $data['preferred_locale']
        );
    }

    public function serialize()
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'second_factor_type'        => (string) $this->secondFactorType,
            'second_factor_identifier'  => $this->secondFactorIdentifier,
            'registration_requested_at' => (string) $this->registrationRequestedAt,
            'identifying_data_id'       => (string) $this->identifyingDataId,
            'registration_code'         => $this->registrationCode,
            'preferred_locale'          => $this->preferredLocale,
        ];
    }
}
