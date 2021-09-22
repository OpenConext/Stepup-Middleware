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
use Surfnet\Stepup\Exception\InvalidArgumentException;
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

/**
 * A second factor whose possession has been proven by the registrant and the registrant's e-mail address has been
 * verified. The registrant must visit a registration authority next.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VerifiedSecondFactor extends AbstractSecondFactor
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    private $id;

    /**
     * @var \Surfnet\Stepup\Identity\Api\Identity
     */
    private $identity;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    private $type;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier
     */
    private $secondFactorIdentifier;

    /**
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    private $registrationRequestedAt;

    /**
     * @var string
     */
    private $registrationCode;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param SecondFactorType $type
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @param DateTime $registrationRequestedAt
     * @param string $registrationCode
     * @return self
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        SecondFactorType $type,
        SecondFactorIdentifier $secondFactorIdentifier,
        DateTime $registrationRequestedAt,
        $registrationCode
    ) {
        if (!is_string($registrationCode)) {
            throw InvalidArgumentException::invalidType('string', 'registrationCode', $registrationCode);
        }

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

    /**
     * @return SecondFactorId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $registrationCode
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @return bool
     */
    public function hasRegistrationCodeAndIdentifier($registrationCode, SecondFactorIdentifier $secondFactorIdentifier)
    {
        return strcasecmp($registrationCode, $this->registrationCode) === 0
            && $secondFactorIdentifier->equals($this->secondFactorIdentifier);
    }

    /**
     * @return bool
     */
    public function canBeVettedNow()
    {
        return !DateTime::now()->comesAfter(
            $this->registrationRequestedAt
                ->add(new DateInterval('P14D'))
                ->endOfDay()
        );
    }

    public function vet($provePossessionSkipped, VettingType $type)
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
                    $type
                )
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
                $type
            )
        );
    }

    public function revoke()
    {
        $this->apply(
            new VerifiedSecondFactorRevokedEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier
            )
        );
    }

    public function complyWithRevocation(IdentityId $authorityId)
    {
        $this->apply(
            new CompliedWithVerifiedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $authorityId
            )
        );
    }

    /**
     * @return VettedSecondFactor
     */
    public function asVetted()
    {
        return VettedSecondFactor::create(
            $this->id,
            $this->identity,
            $this->type,
            $this->secondFactorIdentifier
        );
    }

    public function getLoaLevel(SecondFactorTypeService $secondFactorTypeService): int
    {
        return $secondFactorTypeService->getLevel($this->type);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $secondFactorIdentifierClass = get_class($this->secondFactorIdentifier);

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
