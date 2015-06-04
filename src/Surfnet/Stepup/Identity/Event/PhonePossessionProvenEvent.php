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

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class PhonePossessionProvenEvent extends IdentityEvent implements Forgettable
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
     * @var string
     */
    public $emailVerificationNonce;

    /**
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    public $commonName;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    public $email;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Locale Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId $identityId
     * @param Institution $identityInstitution
     * @param SecondFactorId $secondFactorId
     * @param PhoneNumber $phoneNumber
     * @param EmailVerificationWindow $emailVerificationWindow
     * @param string $emailVerificationNonce
     * @param CommonName $commonName
     * @param Email $email
     * @param Locale $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        EmailVerificationWindow $emailVerificationWindow,
        $emailVerificationNonce,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId = $secondFactorId;
        $this->phoneNumber = $phoneNumber;
        $this->emailVerificationWindow = $emailVerificationWindow;
        $this->emailVerificationNonce = $emailVerificationNonce;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;
        $metadata->secondFactorId         = $this->secondFactorId;
        $metadata->secondFactorType       = new SecondFactorType('sms');
        $metadata->secondFactorIdentifier = (string) $this->phoneNumber;

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
            $data['email_verification_nonce'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
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
            'email_verification_nonce'  => (string) $this->emailVerificationNonce,
            'preferred_locale'          => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return new SensitiveData([
            SensitiveData::EMAIL => $this->email,
            SensitiveData::COMMON_NAME => $this->commonName,
        ]);
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email      = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
    }
}
