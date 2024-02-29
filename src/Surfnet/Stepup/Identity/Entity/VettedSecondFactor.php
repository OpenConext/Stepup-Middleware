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

use Surfnet\Stepup\Identity\Api\Identity;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * A second factor whose possession and its Registrant's identity has been vetted by a Registration Authority.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 */
class VettedSecondFactor extends AbstractSecondFactor
{
    private ?Identity $identity = null;

    private ?SecondFactorId $id = null;

    private ?SecondFactorType $type = null;

    /**
     * @var SecondFactorIdentifier
     */
    private $secondFactorIdentifier;

    private ?VettingType $vettingType = null;

    /**
     * @return VettedSecondFactor
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        SecondFactorType $type,
        SecondFactorIdentifier $secondFactorIdentifier,
        VettingType $vettingType,
    ): self {
        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;
        $secondFactor->vettingType = $vettingType;

        return $secondFactor;
    }

    final public function __construct()
    {
    }

    /**
     * @return SecondFactorId
     */
    public function getId()
    {
        return $this->id;
    }

    public function revoke(): void
    {
        $this->apply(
            new VettedSecondFactorRevokedEvent(
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
            new CompliedWithVettedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $authorityId,
            ),
        );
    }

    public function vettingType(): VettingType
    {
        return $this->vettingType;
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
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
