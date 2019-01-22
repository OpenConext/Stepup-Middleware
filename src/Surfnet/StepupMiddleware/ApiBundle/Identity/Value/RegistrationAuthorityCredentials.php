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
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

final class RegistrationAuthorityCredentials implements \JsonSerializable
{
    /**
     * @var string
     */
    private $identityId;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var CommonName
     */
    private $commonName;

    /**
     * @var Location|null
     */
    private $location;

    /**
     * @var ContactInformation|null
     */
    private $contactInformation;

    /**
     * @var bool
     */
    private $isRa;

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
     * @param bool   $isRa
     * @param bool   $isRaa
     * @param bool   $isSraa
     */
    private function __construct(
        $identityId,
        $isRa,
        $isRaa,
        $isSraa
    ) {
        $this->identityId = $identityId;
        $this->isRa = $isRa;
        $this->isRaa = $isRaa;
        $this->isSraa = $isSraa;
    }

    /**
     * @param Sraa     $sraa
     * @param Identity $identity
     * @return RegistrationAuthorityCredentials
     */
    public static function fromSraa(Sraa $sraa, Identity $identity)
    {
        static::assertEquals($sraa->nameId, $identity->nameId);

        $credentials = new self($identity->id, true, true, true);
        $credentials->commonName = $identity->commonName;

        return $credentials;
    }

    /**
     * @param RaListing $raListing
     * @return RegistrationAuthorityCredentials
     */
    public static function fromRaListing(RaListing $raListing)
    {
        $credentials = new self(
            $raListing->identityId,
            $raListing->role->equals(AuthorityRole::ra()),
            $raListing->role->equals(AuthorityRole::raa()),
            false
        );

        $credentials->institution        = $raListing->institution;
        $credentials->commonName         = $raListing->commonName;
        $credentials->location           = $raListing->location;
        $credentials->contactInformation = $raListing->contactInformation;

        return $credentials;
    }

    /**
     * @param string $nameId
     * @param string $identityNameId
     * @return void
     */
    private static function assertEquals($nameId, $identityNameId)
    {
        Assertion::eq($nameId, $identityNameId);
    }

    /**
     * @return RegistrationAuthorityCredentials
     */
    public function grantSraa()
    {
        $copy = clone $this;
        $copy->isSraa = true;

        return $copy;
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
                'is_ra'               => ($this->isRa || $this->isSraa),
                'is_raa'              => ($this->isRaa || $this->isSraa),
                'is_sraa'             => $this->isSraa,
            ]
        ];
    }

    /**
     * @return string
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * @return Institution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @return CommonName
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getContactInformation()
    {
        return $this->contactInformation;
    }

    /**
     * @return boolean
     */
    public function isRa()
    {
        return $this->isRa;
    }

    /**
     * @return boolean
     */
    public function isRaa()
    {
        return $this->isRaa;
    }

    /**
     * @return boolean
     */
    public function isSraa()
    {
        return $this->isSraa;
    }
}
