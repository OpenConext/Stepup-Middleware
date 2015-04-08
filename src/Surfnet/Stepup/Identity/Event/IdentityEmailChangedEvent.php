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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;

class IdentityEmailChangedEvent extends IdentityEvent
{
    /**
     * @var string
     */
    public $oldEmail;

    /**
     * @var string
     */
    public $newEmail;

    public function __construct(IdentityId $id, Institution $institution, $oldEmail, $newEmail)
    {
        parent::__construct($id, $institution);

        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
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
            $data['old_email'],
            $data['new_email']
        );
    }

    public function serialize()
    {
        return [
            'id'          => (string) $this->identityId,
            'institution' => (string) $this->identityInstitution,
            'old_email'   => $this->oldEmail,
            'new_email'   => $this->newEmail
        ];
    }
}
