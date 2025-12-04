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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\StepupBundle\Security\OtpGenerator;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * A second factor whose possession has been proven by the registrant. The registrant must verify his/her e-mail
 * address to verify this second factor.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class UnverifiedSecondFactor extends AbstractSecondFactor
{
    private ?Identity $identity = null;

    private ?SecondFactorId $id = null;

    private ?SecondFactorType $type = null;

    /**
     * @var SecondFactorIdentifier
     */
    private SecondFactorIdentifier $secondFactorIdentifier;

    private ?EmailVerificationWindow $verificationWindow = null;

    private ?string $verificationNonce = null;

    public static function create(
        SecondFactorId          $id,
        Identity                $identity,
        SecondFactorType        $type,
        SecondFactorIdentifier  $secondFactorIdentifier,
        EmailVerificationWindow $emailVerificationWindow,
        string                  $verificationNonce,
    ): self {
        if ($verificationNonce === '' || $verificationNonce === '0') {
            throw new InvalidArgumentException("'verificationNonce' may not be empty");
        }

        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->verificationWindow = $emailVerificationWindow;
        $secondFactor->verificationNonce = $verificationNonce;

        return $secondFactor;
    }

    final public function __construct()
    {
    }

    public function getId(): ?SecondFactorId
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function hasNonce(string $verificationNonce): bool
    {
        return $this->verificationNonce === $verificationNonce;
    }

    /**
     * @return bool
     */
    public function canBeVerifiedNow(): bool
    {
        return $this->verificationWindow->isOpen();
    }

    public function verifyEmail(): void
    {
        $this->apply(
            new EmailVerifiedEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                DateTime::now(),
                OtpGenerator::generate(8),
                $this->identity->getCommonName(),
                $this->identity->getEmail(),
                $this->identity->getPreferredLocale(),
            ),
        );
    }

    public function revoke(): void
    {
        $this->apply(
            new UnverifiedSecondFactorRevokedEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
            ),
        );
    }

    public function complyWithRevocation(IdentityId $authorityId): void
    {
        $this->apply(
            new CompliedWithUnverifiedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $authorityId,
            ),
        );
    }

    public function asVerified(DateTime $registrationRequestedAt, string $registrationCode): VerifiedSecondFactor
    {
        return VerifiedSecondFactor::create(
            $this->id,
            $this->identity,
            $this->type,
            $this->secondFactorIdentifier,
            $registrationRequestedAt,
            $registrationCode,
        );
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $secondFactorIdentifierClass = $this->secondFactorIdentifier::class;

        $this->secondFactorIdentifier = $secondFactorIdentifierClass::unknown();
    }

    public function getType(): SecondFactorType
    {
        return $this->type;
    }

    public function getIdentifier(): SecondFactorIdentifier
    {
        return $this->secondFactorIdentifier;
    }
}
