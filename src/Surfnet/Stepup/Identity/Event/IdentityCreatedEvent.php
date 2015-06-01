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

class IdentityCreatedEvent extends IdentityEvent
{
    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Locale
     */
    public $preferredLocale;

    /**
     * @var IdentifyingDataId
     */
    public $identifyingDataId;

    public function __construct(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        Locale $preferredLocale,
        IdentifyingDataId $identifyingDataId
    ) {
        parent::__construct($id, $institution);

        $this->nameId = $nameId;
        $this->preferredLocale = $preferredLocale;
        $this->identifyingDataId = $identifyingDataId;
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
            new IdentityId($data['id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
            new Locale($data['preferred_locale']),
            new IdentifyingDataId($data['identifying_data_id'])
        );
    }

    public function serialize()
    {
        return [
            'id'                  => (string) $this->identityId,
            'institution'         => (string) $this->identityInstitution,
            'name_id'             => (string) $this->nameId,
            'preferred_locale'    => (string) $this->preferredLocale,
            'identifying_data_id' => (string) $this->identifyingDataId
        ];
    }
}
