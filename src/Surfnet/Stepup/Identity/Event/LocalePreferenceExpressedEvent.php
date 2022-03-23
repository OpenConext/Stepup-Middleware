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
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

class LocalePreferenceExpressedEvent extends IdentityEvent implements RightToObtainDataInterface
{
    private $whitelist = [
        'id',
        'institution',
        'preferred_locale',
    ];

    /**
     * @var Locale
     */
    public $preferredLocale;

    /**
     * @param IdentityId  $id
     * @param Institution $institution
     * @param Locale      $preferredLocale
     */
    public function __construct(IdentityId $id, Institution $institution, Locale $preferredLocale)
    {
        parent::__construct($id, $institution);

        $this->preferredLocale = $preferredLocale;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    /**
     * @param array $data
     * @return IdentityRenamedEvent The object instance
     */
    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['id']),
            new Institution($data['institution']),
            new Locale($data['preferred_locale'])
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'id'          => (string) $this->identityId,
            'institution' => (string) $this->identityInstitution,
            'preferred_locale' => (string) $this->preferredLocale,
        ];
    }

    public function obtainUserData(): array
    {
        $serializedUserData = $this->serialize();
        $whitelist = array_flip(self::$whitelist);
        return array_intersect_key($serializedUserData, $whitelist);
    }

    public function getAllowlist(): array
    {
        return $this->whitelist;
    }
}
