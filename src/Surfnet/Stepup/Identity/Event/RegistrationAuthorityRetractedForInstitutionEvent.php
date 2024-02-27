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
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class RegistrationAuthorityRetractedForInstitutionEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    private array $allowlist = [
        'identity_id',
        'identity_institution',
        'name_id',
        'ra_institution',
        'email',
        'common_name',
    ];

    /**
     * @var NameId
     */
    public $nameId;

    /**
     * @var CommonName
     */
    public $commonName;

    /**
     * @var Email
     */
    public $email;

    /**
     * @var Institution
     */
    public $raInstitution;

    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Institution $raInstitution
    ) {
        parent::__construct($identityId, $institution);

        $this->nameId     = $nameId;
        $this->commonName = $commonName;
        $this->email      = $email;
        $this->raInstitution = $raInstitution;
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new NameId($data['name_id']),
            CommonName::unknown(),
            Email::unknown(),
            new Institution($data['ra_institution'])
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'identity_id'          => (string) $this->identityId,
            'identity_institution' => (string) $this->identityInstitution,
            'name_id'              => (string) $this->nameId,
            'ra_institution'       => (string) $this->raInstitution,
        ];
    }

    public function getSensitiveData()
    {
        return (new SensitiveData)
            ->withCommonName($this->commonName)
            ->withEmail($this->email);
    }

    public function setSensitiveData(SensitiveData $sensitiveData): void
    {
        $this->email      = $sensitiveData->getEmail();
        $this->commonName = $sensitiveData->getCommonName();
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
