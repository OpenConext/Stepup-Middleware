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

namespace Surfnet\Stepup\Identity;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Api\Identity as IdentityApi;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Token\VerificationCode;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

class Identity extends EventSourcedAggregateRoot implements IdentityApi
{
    /**
     * @var IdentityId
     */
    private $id;

    /**
     * @var NameId
     */
    private $nameId;

    /**
     * @var int
     */
    private $tokenCount;

    public static function create(IdentityId $id, NameId $nameId)
    {
        $identity = new self();
        $identity->apply(new IdentityCreatedEvent($id, $nameId));

        return $identity;
    }

    final public function __construct()
    {
    }

    public function provePossessionOfYubikey(SecondFactorId $secondFactorId, YubikeyPublicId $yubikeyPublicId)
    {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new YubikeyPossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $yubikeyPublicId,
                'Reinier', // @TODO
                'rkip@ibuildings.nl', // @TODO
                VerificationCode::generate(8),
                VerificationCode::generateNonce()
            )
        );
    }

    public function provePossessionOfPhone(SecondFactorId $secondFactorId, PhoneNumber $phoneNumber)
    {
        $this->assertUserMayAddSecondFactor();
        $this->apply(
            new PhonePossessionProvenEvent(
                $this->id,
                $secondFactorId,
                $phoneNumber,
                'Reinier', // @TODO
                'rkip@ibuildings.nl', // @TODO
                VerificationCode::generate(8),
                VerificationCode::generateNonce()
            )
        );
    }

    protected function applyIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $this->id = $event->identityId;
        $this->nameId = $event->nameId;
        $this->tokenCount = 0;
    }

    protected function applyYubikeyPossessionProvenEvent(YubikeyPossessionProvenEvent $event)
    {
        $this->tokenCount++;
    }

    protected function applyPhonePossessionProvenEvent(PhonePossessionProvenEvent $event)
    {
        $this->tokenCount++;
    }

    /**
     * @return string
     */
    public function getAggregateRootId()
    {
        return (string) $this->id;
    }

    /**
     * @throws DomainException
     */
    private function assertUserMayAddSecondFactor()
    {
        if ($this->tokenCount > 0) {
            throw new DomainException('User may not have more than one token');
        }
    }
}
