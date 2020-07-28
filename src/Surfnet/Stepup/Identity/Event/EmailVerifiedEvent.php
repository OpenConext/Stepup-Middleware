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
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class EmailVerifiedEvent extends IdentityEvent implements Forgettable, PossessionProvenAndVerified
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
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier
     */
    private $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $registrationRequestedAt;

    /**
     * @var string
     */
    public $registrationCode;

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
     * @param IdentityId        $identityId
     * @param Institution       $identityInstitution
     * @param SecondFactorId    $secondFactorId
     * @param SecondFactorType  $secondFactorType
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @param DateTime          $registrationRequestedAt
     * @param string            $registrationCode
     * @param CommonName        $commonName
     * @param Email             $email
     * @param Locale            $preferredLocale
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
        $registrationCode,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->secondFactorId          = $secondFactorId;
        $this->secondFactorType        = $secondFactorType;
        $this->secondFactorIdentifier  = $secondFactorIdentifier;
        $this->registrationRequestedAt = $registrationRequestedAt;
        $this->registrationCode        = $registrationCode;
        $this->commonName              = $commonName;
        $this->email                   = $email;
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
        $secondFactorType = new SecondFactorType($data['second_factor_type']);

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            $secondFactorType,
            SecondFactorIdentifierFactory::unknownForType($secondFactorType),
            DateTime::fromString($data['registration_requested_at']),
            $data['registration_code'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
        );
    }

    public function serialize(): array
    {
        return [
            'identity_id'               => (string) $this->identityId,
            'identity_institution'      => (string) $this->identityInstitution,
            'second_factor_id'          => (string) $this->secondFactorId,
            'second_factor_type'        => (string) $this->secondFactorType,
            'registration_requested_at' => (string) $this->registrationRequestedAt,
            'registration_code'         => $this->registrationCode,
            'preferred_locale'          => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->secondFactorIdentifier, $this->secondFactorType);
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email      = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $this->secondFactorIdentifier = $sensitiveData->getSecondFactorIdentifier();
    }
}
