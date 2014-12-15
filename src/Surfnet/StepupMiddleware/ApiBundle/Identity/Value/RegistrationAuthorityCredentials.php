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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Value;

use Assert\Assertion;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Ra;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Raa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

class RegistrationAuthorityCredentials implements \JsonSerializable
{
    /**
     * @var string
     */
    private $identityId;

    /**
     * @var string
     */
    private $institution;

    /**
     * @var string
     */
    private $commonName;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $contactInformation;

    /**
     * @var bool
     */
    private $isRaa;

    /**
     * @var bool
     */
    private $isSraa;

    /**
     * @param string $identityId
     * @param bool   $isRaa
     * @param bool   $isSraa
     */
    private function __construct(
        $identityId,
        $isRaa,
        $isSraa
    ) {
        $this->identityId = $identityId;
        $this->isRaa = $isRaa;
        $this->isSraa = $isSraa;
    }

    /**
     * @param Ra       $ra
     * @param Identity $identity
     * @return RegistrationAuthorityCredentials
     */
    public static function fromRa(Ra $ra, Identity $identity)
    {
        static::assertEquals($ra->nameId, $identity->nameId);

        $credentials = new self($identity->id, false, false);

        $credentials->institution        = $ra->institution;
        $credentials->commonName         = $identity->commonName;
        $credentials->location           = $ra->location;
        $credentials->contactInformation = $ra->contactInformation;

        return $credentials;
    }

    /**
     * @param Raa      $raa
     * @param Identity $identity
     * @return RegistrationAuthorityCredentials
     */
    public static function fromRaa(Raa $raa, Identity $identity)
    {
        static::assertEquals($raa->nameId, $identity->nameId);

        $credentials = new self($identity->id, true, false);

        $credentials->institution        = $raa->institution;
        $credentials->commonName         = $identity->commonName;
        $credentials->location           = $raa->location;
        $credentials->contactInformation = $raa->contactInformation;

        return $credentials;
    }

    /**
     * @param Sraa     $sraa
     * @param Identity $identity
     * @return RegistrationAuthorityCredentials
     */
    public static function fromSraa(Sraa $sraa, Identity $identity)
    {
        static::assertEquals($sraa->nameId, $identity->nameId);

        $credentials = new self($identity->id, true, true);
        $credentials->commonName = $identity->commonName;

        return $credentials;
    }

    /**
     * @param string $nameId
     * @param string $identityNameId
     * @return bool
     */
    private static function assertEquals($nameId, $identityNameId)
    {
        Assertion::eq($nameId, $identityNameId);
    }

    /**
     * @param RegistrationAuthorityCredentials $other
     * @return bool
     */
    public function equals(RegistrationAuthorityCredentials $other)
    {
        return $other->jsonSerialize() === $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->identityId,
            'attributes' => [
                'institution'         => $this->institution,
                'common_name'         => $this->commonName,
                'location'            => $this->location,
                'contact_information' => $this->contactInformation,
                'is_raa'              => ($this->isRaa || $this->isSraa),
                'is_sraa'             => $this->isSraa,
            ]
        ];
    }
}
