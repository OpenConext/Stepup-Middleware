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

use DateInterval;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupBundle\Value\VettingType as StepupVettingType;

/**
 * A second factor whose possession has been proven by the registrant and the registrant's e-mail address has been
 * verified. The registrant must visit a registration authority next.
 *
 * @SuppressWarnings("PHPMD.UnusedPrivateFields")
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class VerifiedSecondFactor extends AbstractSecondFactor
{
    private ?SecondFactorId $id = null;

    private ?Identity $identity = null;

    private ?SecondFactorType $type = null;

    /**
     * @var SecondFactorIdentifier
     */
    private SecondFactorIdentifier $secondFactorIdentifier;

    private ?DateTime $registrationRequestedAt = null;

    private ?string $registrationCode = null;

    public static function create(
        SecondFactorId         $id,
        Identity               $identity,
        SecondFactorType       $type,
        SecondFactorIdentifier $secondFactorIdentifier,
        DateTime               $registrationRequestedAt,
        string                 $registrationCode,
    ): self {
        $secondFactor = new self;
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->registrationRequestedAt = $registrationRequestedAt;
        $secondFactor->registrationCode = $registrationCode;

        return $secondFactor;
    }

    final private function __construct()
    {
    }

    public function getId(): ?SecondFactorId
    {
        return $this->id;
    }

    public function hasRegistrationCodeAndIdentifier(
        string                 $registrationCode,
        SecondFactorIdentifier $secondFactorIdentifier,
    ): bool {
        return strcasecmp($registrationCode, (string)$this->registrationCode) === 0
            && $secondFactorIdentifier->equals($this->secondFactorIdentifier);
    }

    /**
     * @return bool
     */
    public function canBeVettedNow(): bool
    {
        return !DateTime::now()->comesAfter(
            $this->registrationRequestedAt
                ->add(new DateInterval('P14D'))
                ->endOfDay(),
        );
    }

    public function vet(bool $provePossessionSkipped, VettingType $type): void
    {
        if ($provePossessionSkipped) {
            $this->apply(
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $this->identity->getId(),
                    $this->identity->getNameId(),
                    $this->identity->getInstitution(),
                    $this->id,
                    $this->type,
                    $this->secondFactorIdentifier,
                    $this->identity->getCommonName(),
                    $this->identity->getEmail(),
                    $this->identity->getPreferredLocale(),
                    $type,
                ),
            );
            return;
        }

        $this->apply(
            new SecondFactorVettedEvent(
                $this->identity->getId(),
                $this->identity->getNameId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $this->identity->getCommonName(),
                $this->identity->getEmail(),
                $this->identity->getPreferredLocale(),
                $type,
            ),
        );
    }

    public function revoke(): void
    {
        $this->apply(
            new VerifiedSecondFactorRevokedEvent(
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
            new CompliedWithVerifiedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $authorityId,
            ),
        );
    }

    public function asVetted(VettingType $vettingType): VettedSecondFactor
    {
        return VettedSecondFactor::create(
            $this->id,
            $this->identity,
            $this->type,
            $this->secondFactorIdentifier,
            $vettingType,
        );
    }

    public function getLoaLevel(SecondFactorTypeService $secondFactorTypeService): float
    {
        return $secondFactorTypeService->getLevel($this->type, new StepupVettingType(VettingType::TYPE_UNKNOWN));
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $secondFactorIdentifierClass = $this->secondFactorIdentifier::class;

        $identifier = $secondFactorIdentifierClass::unknown();
        assert($identifier instanceof SecondFactorIdentifier);
        $this->secondFactorIdentifier = $identifier;
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
