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

use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;

class VerifiedTokenInformation
{
    private $email;

    private $tokenId;

    private $tokenType;

    private $commonName;

    private $requestedAt;

    private $preferredLocale;

    private $institution;

    private $registrationCode;

    /**
     * @param $email
     * @param $tokenId
     * @param $tokenType
     * @param $commonName
     * @param $requestedAt
     * @param $preferredLocale
     * @param $institution
     * @param $registrationCode
     */
    public function __construct(
        $email,
        $tokenId,
        $tokenType,
        $commonName,
        $requestedAt,
        $preferredLocale,
        $institution,
        $registrationCode
    ) {
        $this->email = $email;
        $this->tokenId = $tokenId;
        $this->tokenType = $tokenType;
        $this->commonName = $commonName;
        $this->requestedAt = $requestedAt;
        $this->preferredLocale = $preferredLocale;
        $this->institution = $institution;
        $this->registrationCode = $registrationCode;
    }

    public static function fromEntity(VerifiedSecondFactor $token, Identity $identity): self
    {
        $tokenInformation = new self(
            (string) $identity->email,
            $token->id,
            $token->type,
            (string) $identity->commonName,
            $token->registrationRequestedAt,
            (string) $identity->preferredLocale,
            $identity->institution,
            $token->registrationCode
        );

        return $tokenInformation;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getTokenId()
    {
        return $this->tokenId;
    }

    public function getTokenType()
    {
        return $this->tokenType;
    }

    public function getCommonName()
    {
        return $this->commonName;
    }

    public function getRequestedAt()
    {
        return $this->requestedAt;
    }

    public function getPreferredLocale()
    {
        return $this->preferredLocale;
    }

    public function getInstitution()
    {
        return $this->institution;
    }

    public function getRegistrationCode()
    {
        return $this->registrationCode;
    }
}
