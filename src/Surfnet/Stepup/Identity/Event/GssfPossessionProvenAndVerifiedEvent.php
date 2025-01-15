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
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class GssfPossessionProvenAndVerifiedEvent extends IdentityEvent implements
    Forgettable,
    PossessionProvenAndVerified,
    RightToObtainDataInterface
{
    /**
     * @var string[]
     */
    private array $allowlist = [
        'identity_id',
        'identity_institution',
        'stepup_provider',
        'registration_requested_at',
        'preferred_locale',
        'second_factor_identifier',
        'type',
        'common_name',
        'email',
    ];

    /**
     * @param IdentityId $identityId
     * @param Institution $identityInstitution
     * @param SecondFactorId $secondFactorId
     * @param StepupProvider $stepupProvider
     * @param GssfId $gssfId
     * @param CommonName $commonName
     * @param Email $email
     * @param Locale $preferredLocale
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId     $identityId,
        Institution    $identityInstitution,
        public SecondFactorId $secondFactorId,
        public StepupProvider $stepupProvider,
        public GssfId         $gssfId,
        public CommonName     $commonName,
        public Email          $email,
        public Locale         $preferredLocale,
        public DateTime       $registrationRequestedAt,
        public string  $registrationCode,
    ) {
        parent::__construct($identityId, $identityInstitution);
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType((string)$this->stepupProvider);
        $metadata->secondFactorIdentifier = $this->gssfId;

        return $metadata;
    }

    public static function deserialize(array $data): self
    {
        // BC compatibility for event replay in test-environment only (2.8.0, fixed in 2.8.1)
        if (!isset($data['preferred_locale'])) {
            $data['preferred_locale'] = 'en_GB';
        }

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new StepupProvider($data['stepup_provider']),
            GssfId::unknown(),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
            DateTime::fromString($data['registration_requested_at']),
            (string)$data['registration_code'],
        );
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
            'identity_institution' => (string)$this->identityInstitution,
            'second_factor_id' => (string)$this->secondFactorId,
            'stepup_provider' => (string)$this->stepupProvider,
            'registration_requested_at' => (string)$this->registrationRequestedAt,
            'registration_code' => $this->registrationCode,
            'preferred_locale' => (string)$this->preferredLocale,
        ];
    }

    public function getSensitiveData(): SensitiveData
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->gssfId, new SecondFactorType((string)$this->stepupProvider));
    }

    public function setSensitiveData(SensitiveData $sensitiveData): void
    {
        $gssfId = $sensitiveData->getSecondFactorIdentifier();
        assert($gssfId instanceof GssfId);
        $this->gssfId = $gssfId;
        $this->email = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
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
