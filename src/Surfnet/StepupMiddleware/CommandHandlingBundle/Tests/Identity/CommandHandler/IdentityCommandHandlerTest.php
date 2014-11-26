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
use DateTime as CoreDateTime;
use Mockery as m;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class IdentityCommandHandlerTest extends CommandHandlerTest
{
    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        return new IdentityCommandHandler(new IdentityRepository($eventStore, $eventBus));
    }

    public function testAnIdentityCanBeCreated()
    {
        $id = self::uuid();
        $institution = 'A Corp.';
        $nameId = md5(__METHOD__);
        $email = 'a@b.c';
        $commonName = 'foobar';

        $command = new CreateIdentityCommand();
        $command->id = $id;
        $command->institution = $institution;
        $command->nameId = $nameId;
        $command->email = $email;
        $command->commonName = $commonName;

        $this->scenario
            ->withAggregateId($id)
            ->given([])
            ->when($command)
            ->then([
                new IdentityCreatedEvent(
                    new IdentityId($id),
                    new Institution($institution),
                    new NameId($nameId),
                    $email,
                    $commonName
                )
            ]);
    }

    /** @runInSeparateProcess */
    public function testAYubikeyPossessionCanBeProven()
    {
        DateTimeHelper::stubNow(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Identity\Token\Token')
            ->shouldReceive('generateHumanToken')->once()->andReturn('code')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new YubikeyPublicId('ccccvfeghijk');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->yubikeyPublicId = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName)])
            ->when($command)
            ->then([
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId,
                    $pubId,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ]);
    }

    public function testYubikeyPossessionCannotBeProvenTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $pubId1 = new YubikeyPublicId('ccccvfeghijk');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->yubikeyPublicId = (string) $pubId1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $pubId1,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ])
            ->when($command);
    }

    /** @runInSeparateProcess */
    public function testAPhonePossessionCanBeProven()
    {
        DateTimeHelper::stubNow(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Identity\Token\Token')
            ->shouldReceive('generateHumanToken')->once()->andReturn('code')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new PhoneNumber('+31612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->phoneNumber = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName)])
            ->when($command)
            ->then([
                new PhonePossessionProvenEvent(
                    $id,
                    $secFacId,
                    $pubId,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ]);
    }

    public function testPhonePossessionCannotBeProvenTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $phoneNumber1 = new PhoneNumber('+31612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new PhonePossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $phoneNumber1,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ])
            ->when($command);
    }

    public function testCannotProvePossessionOfArbitrarySecondFactorTypeTwice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');
        $phoneNumber = new PhoneNumber('+31676543210');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $publicId,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ])
            ->when($command);
    }

    /** @runInSeparateProcess */
    public function testAnUnverifiedSecondFactorsEmailCanBeVerified()
    {
        DateTimeHelper::stubNow(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Identity\Token\Token')
            ->shouldReceive('generateHumanToken')->once()->andReturn('regcode');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secondFactorId,
                    $publicId,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                )
            ])
            ->when($command)
            ->then([
                new EmailVerifiedEvent(
                    $id,
                    $secondFactorId,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email
                )
            ]);
    }

    public function testAVerifiedSecondFactorsEmailCannotBeVerified()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'possession already verified');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secondFactorId,
                    $publicId,
                    DateTime::now(),
                    'nonce',
                    'Foo bar',
                    'a@b.c'
                ),
                new EmailVerifiedEvent(
                    $id,
                    $secondFactorId,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function it_can_create_a_new_identity()
    {
        $createCommand = new CreateIdentityCommand();
        $createCommand->UUID = '1';
        $createCommand->id = '2';
        $createCommand->institution = 'A Corp.';
        $createCommand->nameId = '3';
        $createCommand->email = 'a@b.c';
        $createCommand->commonName = 'foobar';

        $createdEvent = new IdentityCreatedEvent(
            new IdentityId('2'),
            new Institution('A Corp.'),
            new NameId('3'),
            'a@b.c',
            'foobar'
        );

        $this->scenario
            ->given([])
            ->when($createCommand)
            ->then([$createdEvent]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_be_updated()
    {
        $id = new IdentityId('42');
        $createdEvent = new IdentityCreatedEvent(
            $id,
            new Institution('A Corp.'),
            new NameId('3'),
            'a@b.c',
            'foobar'
        );

        $updateCommand = new UpdateIdentityCommand();
        $updateCommand->id = $id;
        $updateCommand->email = 'new@email.com';
        $updateCommand->commonName = 'Henk';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent])
            ->when($updateCommand)
            ->then([
                new IdentityRenamedEvent($id, 'foobar', 'Henk'),
                new IdentityEmailChangedEvent($id, 'a@b.c', 'new@email.com')
            ]);
    }
}
