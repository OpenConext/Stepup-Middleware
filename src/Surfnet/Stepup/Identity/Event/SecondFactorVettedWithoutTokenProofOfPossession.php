<?php

/**
 * Copyright 2020 SURFnet bv
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
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\Stepup\Identity\Value\SelfVetVettingType;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecondFactorVettedWithoutTokenProofOfPossession extends IdentityEvent implements Forgettable
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\NameId
     */
    public $nameId;

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
    public $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\Identity\Value\DocumentNumber
     */
    public $documentNumber;

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

    /** @var VettingType */
    public $vettingType;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        NameId $nameId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        SecondFactorIdentifier $secondFactorIdentifier,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale,
        VettingType $vettingType
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId                 = $nameId;
        $this->secondFactorId         = $secondFactorId;
        $this->secondFactorType       = $secondFactorType;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->commonName             = $commonName;
        $this->email                  = $email;
        $this->preferredLocale        = $preferredLocale;
        $this->vettingType = $vettingType;
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;
        $metadata->secondFactorId         = $this->secondFactorId;
        $metadata->secondFactorType       = $this->secondFactorType;
        $metadata->secondFactorIdentifier = $this->secondFactorIdentifier;
        $metadata->vettingType = $this->vettingType;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        $secondFactorType = new SecondFactorType($data['second_factor_type']);

        // BC fix for older events without a vetting type, they default back to ON_PREMISE.
        $vettingType = new UnknownVettingType();
        if (isset($data['vetting_type']['type'])) {
            switch ($data['vetting_type']['type']) {
                case VettingType::TYPE_SELF_VET:
                    $vettingType = SelfVetVettingType::deserialize($data['vetting_type']);
                    break;
                case VettingType::TYPE_ON_PREMISE:
                    $vettingType = OnPremiseVettingType::deserialize($data['vetting_type']);
                    break;
            }
        } elseif (isset($data['document_number'])) {
            $vettingType = new OnPremiseVettingType(new DocumentNumber($data['vetting_type']));
        }

        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            $secondFactorType,
            SecondFactorIdentifierFactory::unknownForType($secondFactorType),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
            $vettingType
        );
    }

    public function serialize(): array
    {
        return [
            'identity_id'              => (string) $this->identityId,
            'name_id'                  => (string) $this->nameId,
            'identity_institution'     => (string) $this->identityInstitution,
            'second_factor_id'         => (string) $this->secondFactorId,
            'second_factor_type'       => (string) $this->secondFactorType,
            'preferred_locale'         => (string) $this->preferredLocale,
            'vetting_type' => $this->vettingType->jsonSerialize(),
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email)
            ->withSecondFactorIdentifier($this->secondFactorIdentifier, $this->secondFactorType)
            ->withVettingType($this->vettingType);
    }

    public function setSensitiveData(SensitiveData $sensitiveData)
    {
        $this->email          = $sensitiveData->getEmail();
        $this->commonName     = $sensitiveData->getCommonName();
        $this->secondFactorIdentifier = $sensitiveData->getSecondFactorIdentifier();
        $this->vettingType = $sensitiveData->getVettingType();
    }
}