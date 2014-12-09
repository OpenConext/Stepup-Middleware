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

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class SecondFactorVettedEvent extends IdentityEvent
{
    /**
     * @var SecondFactorId
     */
    public $secondFactorId;

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
     * @var Institution
     */
    private $institution;

    /**
     * @param IdentityId $identityId
     * @param Institution $institution
     * @param SecondFactorId $secondFactorId
     * @param string $documentNumber
     * @param string $commonName
     * @param string $email
     * @param string $preferredLocale
     */
    public function __construct(
        IdentityId $identityId,
        Institution $institution,
        SecondFactorId $secondFactorId,
        $documentNumber,
        $commonName,
        $email,
        $preferredLocale
    ) {
        parent::__construct($identityId);

        $this->secondFactorId = $secondFactorId;
        $this->documentNumber = $documentNumber;
        $this->commonName = $commonName;
        $this->email = $email;
        $this->preferredLocale = $preferredLocale;
        $this->institution = $institution;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new IdentityId($data['identity_id']),
            new Institution($data['institution']),
            new SecondFactorId($data['second_factor_id']),
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
            'institution' => (string) $this->institution,
            'second_factor_id' => (string) $this->secondFactorId,
            'document_number' => $this->documentNumber,
            'common_name' => $this->commonName,
            'email' => $this->email,
            'preferred_locale' => $this->preferredLocale,
        ];
    }
}
