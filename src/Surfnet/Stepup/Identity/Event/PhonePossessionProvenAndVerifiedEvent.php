<?php

/**
 * Copyright 2018 SURFnet bv
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
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class PhonePossessionProvenAndVerifiedEvent extends IdentityEvent implements Forgettable, PossessionProvenAndVerified, RightToObtainDataInterface
{
    private $allowlist = [
        'identity_id',
        'identity_institution',
        'second_factor_id',
        'registration_requested_at',
        'preferred_locale',
        'second_factor_type',
        'second_factor_identifier',
        'email',
        'common_name',
    ];

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\PhoneNumber
     */
    public $phoneNumber;

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
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $registrationRequestedAt;

    /**
     * @var string
     */
    public $registrationCode;

    /**
     * @param IdentityId $identityId
     * @param Institution $identityInstitution
     * @param SecondFactorId $secondFactorId
     * @param PhoneNumber $phoneNumber
     * @param CommonName $commonName
     * @param Email $email
     * @param Locale $locale
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     */
    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        CommonName $commonName,
        Email $email,
        Locale $locale,
        DateTime $registrationRequestedAt,
        $registrationCode
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId = $secondFactorId;
        $this->phoneNumber = $phoneNumber;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $locale;
        $this->registrationRequestedAt = $registrationRequestedAt;
        $this->registrationCode = $registrationCode;
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;
        $metadata->secondFactorId         = $this->secondFactorId;
        $metadata->secondFactorType       = new SecondFactorType('sms');
        $metadata->secondFactorIdentifier = $this->phoneNumber;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        // BC compatibility for event replay in test-environment only (2.8.0, fixed in 2.8.1)
        if (!isset($data['preferred_locale'])) {
            $data['preferred_locale'] = 'en_GB';
        }

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            PhoneNumber::unknown(),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
            DateTime::fromString($data['registration_requested_at']),
            (string) $data['registration_code']
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'registration_requested_at'   => (string) $this->registrationRequestedAt,
            'registration_code'           => $this->registrationCode,
            'preferred_locale'            => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->phoneNumber, new SecondFactorType('sms'));
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->phoneNumber = $sensitiveData->getSecondFactorIdentifier();
        $this->email = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
    }

    public function obtainUserData(): array
    {
        $serializedPublicUserData = $this->serialize();
        $serializedSensitiveUserData = $this->getSensitiveData()->serialize();
        return array_merge($serializedPublicUserData, $serializedSensitiveUserData);
    }

    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}
