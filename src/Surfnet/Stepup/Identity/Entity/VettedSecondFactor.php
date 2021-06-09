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
use Surfnet\Stepup\Identity\Event\MoveSecondFactorEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * A second factor whose possession and its Registrant's identity has been vetted by a Registration Authority.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 */
class VettedSecondFactor extends AbstractSecondFactor
{
    /**
     * @var \Surfnet\Stepup\Identity\Api\Identity
     */
    private $identity;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    private $id;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    private $type;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier
     */
    private $secondFactorIdentifier;

    /**
     * @param SecondFactorId $id
     * @param Identity $identity
     * @param SecondFactorType $type
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @return VettedSecondFactor
     */
    public static function create(
        SecondFactorId $id,
        Identity $identity,
        SecondFactorType $type,
        SecondFactorIdentifier $secondFactorIdentifier
    ) {
        $secondFactor = new self();
        $secondFactor->id = $id;
        $secondFactor->identity = $identity;
        $secondFactor->type = $type;
        $secondFactor->secondFactorIdentifier = $secondFactorIdentifier;

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

    public function move(Identity $targetIdentity, NameId $sourceNameId)
    {
        $this->apply(
            new MoveSecondFactorEvent(
                $targetIdentity->getId(),
                $sourceNameId,
                $targetIdentity->getNameId(),
                $targetIdentity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $targetIdentity->getCommonName(),
                $targetIdentity->getEmail(),
                $targetIdentity->getPreferredLocale()
            )
        );
    }

    public function revoke()
    {
        $this->apply(
            new VettedSecondFactorRevokedEvent(
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
            new CompliedWithVettedSecondFactorRevocationEvent(
                $this->identity->getId(),
                $this->identity->getInstitution(),
                $this->id,
                $this->type,
                $this->secondFactorIdentifier,
                $authorityId
            )
        );
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $secondFactorIdentifierClass = get_class($this->secondFactorIdentifier);

        $this->secondFactorIdentifier = $secondFactorIdentifierClass::unknown();
    }

    public function getType()
    {
        return $this->type;
    }
}
