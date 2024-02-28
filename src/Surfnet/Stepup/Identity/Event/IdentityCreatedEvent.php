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
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class IdentityCreatedEvent extends IdentityEvent implements Forgettable, RightToObtainDataInterface
{
    private array $allowlist = [
        'id',
        'institution',
        'name_id',
        'preferred_locale',
        'common_name',
        'email',
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
     * @var Locale
     */
    public $preferredLocale;

    public function __construct(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        CommonName $commonName,
        Email $email,
        Locale $preferredLocale,
    ) {
        parent::__construct($id, $institution);

        $this->nameId = $nameId;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    public static function deserialize(array $data): self
    {
        return new self(
            new IdentityId($data['id']),
            new Institution($data['institution']),
            new NameId($data['name_id']),
            CommonName::unknown(),
            Email::unknown(),
            new Locale($data['preferred_locale']),
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'id' => (string)$this->identityId,
            'institution' => (string)$this->identityInstitution,
            'name_id' => (string)$this->nameId,
            'preferred_locale' => (string)$this->preferredLocale,
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
        $this->email = $sensitiveData->getEmail();
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
