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

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;

class VerifiedTokenInformation
{
    public function __construct(
        private readonly string $email,
        private readonly string $tokenId,
        private readonly string $tokenType,
        private readonly string $commonName,
        private readonly DateTime $requestedAt,
        private readonly string $preferredLocale,
        private readonly Institution $institution,
        private readonly string $registrationCode,
    ) {
    }

    public static function fromEntity(VerifiedSecondFactor $token, Identity $identity): self
    {
        return new self(
            (string)$identity->email,
            $token->id,
            $token->type,
            (string)$identity->commonName,
            $token->registrationRequestedAt,
            (string)$identity->preferredLocale,
            $identity->institution,
            $token->registrationCode,
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getCommonName(): string
    {
        return $this->commonName;
    }

    public function getRequestedAt(): DateTime
    {
        return $this->requestedAt;
    }

    public function getPreferredLocale(): string
    {
        return $this->preferredLocale;
    }

    public function getInstitution(): Institution
    {
        return $this->institution;
    }

    public function getRegistrationCode(): string
    {
        return $this->registrationCode;
    }
}
