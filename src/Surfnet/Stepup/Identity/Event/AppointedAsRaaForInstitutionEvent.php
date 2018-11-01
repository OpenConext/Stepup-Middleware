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

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;

class AppointedAsRaaForInstitutionEvent extends IdentityEvent
{
    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var Institution
     */
    public $raInstitution;

    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        NameId $nameId,
        Institution $raInstitution
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->nameId = $nameId;
        $this->raInstitution = $raInstitution;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->raInstitution = $this->raInstitution;

        return $metadata;
    }

    /**
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
            new Institution($data['ra_institution'])
        );
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'identity_id'    => (string) $this->identityId,
            'institution'    => (string) $this->identityInstitution,
            'name_id'        => (string) $this->nameId,
            'ra_institution' => (string) $this->raInstitution,
        ];
    }
}
