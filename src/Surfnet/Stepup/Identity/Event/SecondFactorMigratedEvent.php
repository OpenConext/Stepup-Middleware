<?php

/**
 * Copyright 2021 SURFnet bv
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
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\Stepup\Identity\Value\VettingTypeFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecondFactorMigratedEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    private $allowlist = [
        'identity_id',
        'source_institution',
        'target_name_id',
        'identity_institution',
        'second_factor_id',
        'new_second_factor_id',
        'second_factor_type',
        'preferred_locale',
        'second_factor_identifier',
        'common_name',
        'email',
    ];

    /**
     * @var Institution
     */
    private $sourceInstitution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\NameId
     */
    public $targetNameId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $newSecondFactorId;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    public $secondFactorType;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier
     */
    public $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    public $commonName;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Email
     */
    public $email;
    /**
     * @var \Surfnet\Stepup\Identity\Value\Locale
     */
    public $preferredLocale;
    /**
     * @var VettingType
     */
    public $vettingType;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        NameId $targetNameId,
        Institution $targetInstitution,
        Institution $sourceInstitution,
        SecondFactorId $secondFactorId,
        SecondFactorId $newSecondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        VettingType $vettingType,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $targetInstitution);

        $this->sourceInstitution = $sourceInstitution;
        $this->targetNameId = $targetNameId;
        $this->secondFactorId = $secondFactorId;
        $this->newSecondFactorId = $newSecondFactorId;
        $this->secondFactorType = $secondFactorType;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->vettingType = $vettingType;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = $this->secondFactorType;
        $metadata->secondFactorIdentifier = $this->secondFactorIdentifier;
        $metadata->raInstitution = $this->sourceInstitution;
        return $metadata;
    }

    public static function deserialize(array $data)
    {
        $secondFactorType = new SecondFactorType($data['second_factor_type']);
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['target_name_id']),
            new Institution($data['identity_institution']),
            new Institution($data['source_institution']),
            new SecondFactorId($data['second_factor_id']),
            new SecondFactorId($data['new_second_factor_id']),
            $secondFactorType,
            SecondFactorIdentifierFactory::unknownForType($secondFactorType),
            VettingTypeFactory::fromData($data['vetting_type']),
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
            'identity_id' => (string)$this->identityId,
            'source_institution' => (string)$this->sourceInstitution,
            'target_name_id' => (string)$this->targetNameId,
            'identity_institution' => (string)$this->identityInstitution,
            'second_factor_id' => (string)$this->secondFactorId,
            'new_second_factor_id' => (string)$this->newSecondFactorId,
            'vetting_type' => $this->vettingType->jsonSerialize(),
            'second_factor_type' => (string) $this->secondFactorType,
            'preferred_locale' => (string) $this->preferredLocale,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withVettingType($this->vettingType)
            ->withSecondFactorIdentifier($this->secondFactorIdentifier, $this->secondFactorType);
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->secondFactorIdentifier = $sensitiveData->getSecondFactorIdentifier();
        $this->commonName = $sensitiveData->getCommonName();
        $this->email = $sensitiveData->getEmail();
        $this->vettingType = $sensitiveData->getVettingType();
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
