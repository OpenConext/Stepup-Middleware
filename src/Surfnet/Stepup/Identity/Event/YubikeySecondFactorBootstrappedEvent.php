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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

final class YubikeySecondFactorBootstrappedEvent extends IdentityEvent implements
    Forgettable,
    RightToObtainDataInterface
{
    /**
     * @var string[]
     */
    private array $allowlist = [
        'identity_id',
        'name_id',
        'identity_institution',
        'preferred_locale',
        'second_factor_id',
        'second_factor_identifier',
        'second_factor_type',
        'email',
        'common_name',
    ];

    /**
     * @var Institution
     */
    public Institution $institution;

    public function __construct(
        IdentityId $identityId,
        public NameId $nameId,
        Institution $institution,
        public CommonName $commonName,
        public Email $email,
        public Locale $preferredLocale,
        public SecondFactorId $secondFactorId,
        public YubikeyPublicId $yubikeyPublicId,
    ) {
        parent::__construct($identityId, $institution);
        $this->institution = $institution;
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType('yubikey');
        $metadata->secondFactorIdentifier = $this->yubikeyPublicId;

        return $metadata;
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     *
     * @return array<string, mixed>
     */
    public function serialize(): array
    {
        return [
            'identity_id' => (string)$this->identityId,
            'name_id' => (string)$this->nameId,
            'identity_institution' => (string)$this->identityInstitution,
            'preferred_locale' => (string)$this->preferredLocale,
            'second_factor_id' => (string)$this->secondFactorId,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['identity_institution']),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
            new SecondFactorId($data['second_factor_id']),
            YubikeyPublicId::unknown(),
        );
    }

    public function getSensitiveData(): SensitiveData
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->yubikeyPublicId, new SecondFactorType('yubikey'));
    }

    public function setSensitiveData(SensitiveData $sensitiveData): void
    {
        $this->email = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $yubikeyPublicId = $sensitiveData->getSecondFactorIdentifier();
        assert($yubikeyPublicId instanceof YubikeyPublicId);
        $this->yubikeyPublicId = $yubikeyPublicId;
    }

    public function obtainUserData(): array
    {
        $serializedPublicUserData = $this->serialize();
        $serializedSensitiveUserData = $this->getSensitiveData()->serialize();
        return array_merge($serializedPublicUserData, $serializedSensitiveUserData);
    }

    /**
     * @return string[]
     */
    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}
