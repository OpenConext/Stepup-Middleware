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

namespace Surfnet\Stepup\Identity\Event;

use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

/**
 * PhoneRecoveryTokenPossessionProvenEvent
 *
 * This event is recorded when the user prove possession of a Phone (SMS)
 * based Recovery Token.
 */
class PhoneRecoveryTokenPossessionProvenEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    private $allowlist = [
        'identity_id',
        'identity_institution',
        'recovery_token_id',
        'preferred_locale',
        'email',
        'common_name',
    ];

    /**
     * @var \Surfnet\Stepup\Identity\Value\RecoveryTokenId
     */
    public $recoveryTokenId;

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

    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        RecoveryTokenId $recoveryTokenId,
        PhoneNumber $phoneNumber,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->recoveryTokenId = $recoveryTokenId;
        $this->phoneNumber = $phoneNumber;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new RecoveryTokenId($data['recovery_token_id']),
            PhoneNumber::unknown(),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'identity_id' => (string) $this->identityId,
            'identity_institution' => (string) $this->identityInstitution,
            'recovery_token_id' => (string) $this->recoveryTokenId,
            'recovery_token_type' => RecoveryTokenType::TYPE_SMS,
            'preferred_locale' => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withRecoveryTokenSecret($this->phoneNumber, RecoveryTokenType::sms());
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $this->phoneNumber = $sensitiveData->getRecoveryTokenIdentifier();
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
