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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

/**
 * @deprecated This event is superseded by the AppointedAsRaForInstitutionEvent because an RA institution was needed
 */
class AppointedAsRaEvent extends IdentityEvent implements RightToObtainDataInterface
{
    private array $allowlist = [
        'identity_id',
        'institution',
        'name_id',
    ];

    /**
     * @var NameId
     */
    public $nameId;

    public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        NameId $nameId,
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->nameId = $nameId;
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    /**
     * @return mixed The object instance
     */
    public static function deserialize(array $data): self
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
        );
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'identity_id' => (string)$this->identityId,
            'institution' => (string)$this->identityInstitution,
            'name_id' => (string)$this->nameId,
        ];
    }

    public function obtainUserData(): array
    {
        return $this->serialize();
    }

    public function getAllowlist(): array
    {
        return $this->allowlist;
    }
}
