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

use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;

class PhonePossessionProvenEvent extends IdentityEvent
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\PhoneNumber
     */
    public $phoneNumber;

    /**
     * @var \Surfnet\Stepup\Identity\Value\EmailVerificationWindow
     */
    public $emailVerificationWindow;

    /**
     * @var IdentifyingDataId
     */
    public $identifyingDataId;

    /**
     * @var string
     */
    public $emailVerificationNonce;

    /**
     * @var string Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId              $identityId
     * @param Institution             $identityInstitution
     * @param SecondFactorId          $secondFactorId
     * @param PhoneNumber             $phoneNumber
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param IdentifyingDataId       $identifyingDataId
     * @param string                  $emailVerificationNonce
     * @param string                  $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        EmailVerificationWindow $emailVerificationWindow,
        IdentifyingDataId $identifyingDataId,
        $emailVerificationNonce,
        $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId = $secondFactorId;
        $this->phoneNumber = $phoneNumber;
        $this->emailVerificationWindow = $emailVerificationWindow;
        $this->identifyingDataId = $identifyingDataId;
        $this->emailVerificationNonce = $emailVerificationNonce;
        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType('sms');

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new PhoneNumber($data['phone_number']),
            EmailVerificationWindow::deserialize($data['email_verification_window']),
            new IdentifyingDataId($data['identifying_data_id']),
            $data['email_verification_nonce'],
            $data['preferred_locale']
        );
    }

    public function serialize()
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'phone_number'              => (string) $this->phoneNumber,
            'email_verification_window' => $this->emailVerificationWindow->serialize(),
            'identifying_data_id'       => (string) $this->identifyingDataId,
            'email_verification_nonce'  => (string) $this->emailVerificationNonce,
            'preferred_locale'          => $this->preferredLocale,
        ];
    }
}
