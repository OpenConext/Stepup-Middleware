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

class GssfPossessionProvenEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    /**
     * @var string[] 
     */
    private array $allowlist = [
        'identity_id',
        'identity_institution',
        'second_factor_id',
        'stepup_provider',
        'preferred_locale',
        'second_factor_identifier',
        'common_name',
        'email',
    ];

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId              $identityId,
        Institution             $identityInstitution,
        public SecondFactorId          $secondFactorId,
        public StepupProvider          $stepupProvider,
        public GssfId                  $gssfId,
        public bool             $emailVerificationRequired,
        public EmailVerificationWindow $emailVerificationWindow,
        public string           $emailVerificationNonce,
        public CommonName              $commonName,
        public Email                   $email,
        /**
         * @var Locale Eg. "en_GB"
         */
        public Locale                  $preferredLocale,
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
        if (!isset($data['email_verification_required'])) {
            $data['email_verification_required'] = true;
        }

        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new StepupProvider($data['stepup_provider']),
            GssfId::unknown(),
            $data['email_verification_required'],
            EmailVerificationWindow::deserialize($data['email_verification_window']),
            $data['email_verification_nonce'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
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
            'email_verification_required' => $this->emailVerificationRequired,
            'email_verification_window' => $this->emailVerificationWindow->serialize(),
            'email_verification_nonce' => $this->emailVerificationNonce,
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
        $this->email = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
        $gssfId = $sensitiveData->getSecondFactorIdentifier();
        assert($gssfId instanceof GssfId);
        $this->gssfId = $gssfId;
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
