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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;

class IdentityCommandHandlerTest extends CommandHandlerTest
{
    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        return new IdentityCommandHandler(new IdentityRepository($eventStore, $eventBus));
    }

    public function testAnIdentityCanBeCreated()
    {
        $id = self::uuid();
        $nameId = md5(__METHOD__);

        $command = new CreateIdentityCommand();
        $command->id = $id;
        $command->nameId = $nameId;

        $this->scenario
            ->withAggregateId($id)
            ->given([])
            ->when($command)
            ->then([new IdentityCreatedEvent(new IdentityId($id), new NameId($nameId))]);
    }

    public function testAYubikeyPossessionCanBeProven()
    {
        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new YubikeyPublicId('ccccvfeghijk');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->yubikeyPublicId = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $nameId)])
            ->when($command)
            ->then([new YubikeyPossessionProvenEvent($id, $secFacId, $pubId)]);
    }

    public function testYubikeyPossessionCannotBeProvenTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $secFacId1 = new SecondFactorId(self::uuid());
        $secFacId2 = new SecondFactorId(self::uuid());
        $pubId1 = new YubikeyPublicId('ccccvfeghijk');
        $pubId2 = new YubikeyPublicId('ccccvfeghidd');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->yubikeyPublicId = (string) $pubId1;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $nameId), new YubikeyPossessionProvenEvent($id, $secFacId1, $pubId1)])
            ->when($command)
            ->then([new YubikeyPossessionProvenEvent($id, $secFacId2, $pubId2)]);
    }

    public function testAPhonePossessionCanBeProven()
    {
        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new PhoneNumber('+31612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->phoneNumber = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $nameId)])
            ->when($command)
            ->then([new PhonePossessionProvenEvent($id, $secFacId, $pubId)]);
    }

    public function testPhonePossessionCannotBeProvenTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $secFacId1 = new SecondFactorId(self::uuid());
        $secFacId2 = new SecondFactorId(self::uuid());
        $phoneNumber1 = new PhoneNumber('+31612345678');
        $phoneNumber2 = new PhoneNumber('+31676543210');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber1;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $nameId), new PhonePossessionProvenEvent($id, $secFacId1, $phoneNumber1)])
            ->when($command)
            ->then([new PhonePossessionProvenEvent($id, $secFacId2, $phoneNumber2)]);
    }

    public function testCannotProvePossessionOfArbitrarySecondFactorTypeTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $secFacId1 = new SecondFactorId(self::uuid());
        $secFacId2 = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');
        $phoneNumber = new PhoneNumber('+31676543210');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $nameId), new YubikeyPossessionProvenEvent($id, $secFacId1, $publicId)])
            ->when($command)
            ->then([new PhonePossessionProvenEvent($id, $secFacId2, $phoneNumber)]);
    }
}
