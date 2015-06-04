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
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class SecondFactorVettedEvent extends IdentityEvent implements Forgettable
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
     * @var string
     */
    public $secondFactorIdentifier;

    /**
     * @var string
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
     * @var Locale Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId        $identityId
     * @param NameId            $nameId
     * @param Institution       $institution
     * @param SecondFactorId    $secondFactorId
     * @param SecondFactorType  $secondFactorType
     * @param string            $secondFactorIdentifier
     * @param string            $documentNumber
     * @param CommonName        $commonName
     * @param Email             $email
     * @param Locale            $preferredLocale
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        NameId $nameId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        $secondFactorIdentifier,
        $documentNumber,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId                 = $nameId;
        $this->secondFactorId         = $secondFactorId;
        $this->secondFactorType       = $secondFactorType;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->documentNumber         = $documentNumber;
        $this->commonName             = $commonName;
        $this->email                  = $email;
        $this->preferredLocale        = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;
        $metadata->secondFactorId         = $this->secondFactorId;
        $metadata->secondFactorType       = $this->secondFactorType;
        $metadata->secondFactorIdentifier = $this->secondFactorIdentifier;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new SecondFactorType($data['second_factor_type']),
            $data['second_factor_identifier'],
            $data['document_number'],
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale'])
        );
    }

    public function serialize()
    {
        return [
            'identity_id'              => (string) $this->identityId,
            'name_id'                  => (string) $this->nameId,
            'identity_institution'     => (string) $this->identityInstitution,
            'second_factor_id'         => (string) $this->secondFactorId,
            'second_factor_type'       => (string) $this->secondFactorType,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'document_number'          => $this->documentNumber,
            'preferred_locale'         => (string) $this->preferredLocale,
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
