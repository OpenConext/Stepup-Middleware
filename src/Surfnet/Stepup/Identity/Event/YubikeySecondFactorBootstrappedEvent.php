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
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;

final class YubikeySecondFactorBootstrappedEvent extends IdentityEvent
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\NameId
     */
    public $nameId;

    /**
     * @var Institution
     */
    public $institution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Locale
     */
    public $preferredLocale;

    /**
     * @var IdentifyingDataId
     */
    public $identifyingDataId;

    /**
     * @var SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\YubikeyPublicId
     */
    public $yubikeyPublicId;

    public function __construct(
        IdentityId $identityId,
        NameId $nameId,
        Institution $institution,
        Locale $preferredLocale,
        IdentifyingDataId $identifyingDataId,
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId = $nameId;
        $this->institution = $institution;
        $this->preferredLocale = $preferredLocale;
        $this->identifyingDataId = $identifyingDataId;
        $this->secondFactorId = $secondFactorId;
        $this->yubikeyPublicId = $yubikeyPublicId;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = new SecondFactorType('yubikey');
        $metadata->secondFactorIdentifier = (string) $this->yubikeyPublicId;

        return $metadata;
    }

    public function serialize()
    {
        return [
            'identity_id'          => (string) $this->identityId,
            'name_id'              => (string) $this->nameId,
            'identity_institution' => (string) $this->identityInstitution,
            'preferred_locale'     => (string) $this->preferredLocale,
            'identifying_data_id'  => (string) $this->identifyingDataId,
            'second_factor_id'     => (string) $this->secondFactorId,
            'yubikey_public_id'    => (string) $this->yubikeyPublicId,
        ];
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new NameId($data['name_id']),
            new Institution($data['identity_institution']),
            new Locale($data['preferred_locale']),
            new IdentifyingDataId($data['identifying_data_id']),
            new SecondFactorId($data['second_factor_id']),
            new YubikeyPublicId($data['yubikey_public_id'])
        );
    }
}
