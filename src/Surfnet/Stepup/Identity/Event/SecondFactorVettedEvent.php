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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class SecondFactorVettedEvent extends IdentityEvent
{
    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var Institution
     */
    public $institution;

    /**
     * @var SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var string
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
     * @var string
     */
    public $commonName;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string Eg. "en_GB"
     */
    public $preferredLocale;

    /**
     * @param IdentityId $identityId
     * @param NameId $nameId
     * @param Institution $institution
     * @param SecondFactorId $secondFactorId
     * @param string $secondFactorType
     * @param string $secondFactorIdentifier
     * @param string $documentNumber
     * @param string $commonName
     * @param string $email
     * @param string $preferredLocale
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IdentityId $identityId,
        NameId $nameId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        $secondFactorType,
        $secondFactorIdentifier,
        $documentNumber,
        $commonName,
        $email,
        $preferredLocale
    ) {
        parent::__construct($identityId);

        $this->institution = $institution;
        $this->nameId = $nameId;
        $this->secondFactorId = $secondFactorId;
        $this->secondFactorType = $secondFactorType;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->documentNumber = $documentNumber;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['institution']),
            new SecondFactorId($data['second_factor_id']),
            $data['second_factor_type'],
            $data['second_factor_identifier'],
            $data['document_number'],
            $data['common_name'],
            $data['email'],
            $data['preferred_locale']
        );
    }

    public function serialize()
    {
        return [
            'identity_id' => (string) $this->identityId,
            'name_id' => (string) $this->nameId,
            'institution' => (string) $this->institution,
            'second_factor_id' => (string) $this->secondFactorId,
            'second_factor_type' => $this->secondFactorType,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'document_number' => $this->documentNumber,
            'common_name' => $this->commonName,
            'email' => $this->email,
            'preferred_locale' => $this->preferredLocale,
        ];
    }
}
