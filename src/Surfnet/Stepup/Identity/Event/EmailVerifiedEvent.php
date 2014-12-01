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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class EmailVerifiedEvent extends IdentityEvent
{
    /**
     * @var SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var DateTime
     */
    public $registrationRequestedAt;

    /**
     * @var string
     */
    public $registrationCode;

    /**
     * @var string
     */
    public $commonName;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId $identityId
     * @param SecondFactorId $secondFactorId
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     * @param string $commonName
     * @param string $email
     * @param string $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        DateTime $registrationRequestedAt,
        $registrationCode,
        $commonName,
        $email,
        $preferredLocale
    ) {
        parent::__construct($identityId);

        $this->secondFactorId = $secondFactorId;
        $this->registrationRequestedAt = $registrationRequestedAt;
        $this->registrationCode = $registrationCode;
        $this->identityId = $identityId;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new SecondFactorId($data['second_factor_id']),
            DateTime::fromString($data['registration_requested_at']),
            $data['registration_code'],
            $data['common_name'],
            $data['email'],
            'en_GB'
        );
    }

    public function serialize()
    {
        return [
            'identity_id' => (string) $this->identityId,
            'second_factor_id' => (string) $this->secondFactorId,
            'registration_requested_at' => (string) $this->registrationRequestedAt,
            'registration_code' => $this->registrationCode,
            'common_name' => $this->commonName,
            'email' => $this->email,
        ];
    }
}
