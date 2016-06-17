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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata as MessageMetadata;
use DateTime as CoreDateTime;
use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\DateTime\UtcDateTime as StepupDateTime;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\AuditLogProjector;
use Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event\EventStub;

final class AuditLogProjectorTest extends TestCase
{
    private static $actorCommonName = 'Actor CommonName';

    public function auditable_events()
    {
        return [
            'no actor, with second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata(),
                    new EventStub($this->createAuditLogMetadata(
                        new IdentityId('abcd'),
                        new Institution('efgh'),
                        new SecondFactorId('ijkl'),
                        new SecondFactorType('sms')
                    )),
                    BroadwayDateTime::fromString('1970-01-01T00:00:00.000')
                ),
                $this->createExpectedAuditLogEntry(
                    null,
                    null,
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    new SecondFactorId('ijkl'),
                    new SecondFactorType('sms'),
                    'Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event\EventStub',
                    new StepupDateTime(new CoreDateTime('1970-01-01T00:00:00.000'))
                )
            ],
            'no actor, without second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata(),
                    new EventStub($this->createAuditLogMetadata(
                        new IdentityId('abcd'),
                        new Institution('efgh'),
                        null,
                        null
                    )),
                    BroadwayDateTime::fromString('1970-01-01T00:00:00.000')
                ),
                $this->createExpectedAuditLogEntry(
                    null,
                    null,
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    null,
                    null,
                    'Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event\EventStub',
                    new StepupDateTime(new CoreDateTime('1970-01-01T00:00:00.000'))
                )
            ],
            'with actor, with second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata([
                        'actorId' => '0123',
                        'actorInstitution' => '4567',
                    ]),
                    new EventStub($this->createAuditLogMetadata(
                        new IdentityId('abcd'),
                        new Institution('efgh'),
                        new SecondFactorId('ijkl'),
                        new SecondFactorType('sms')
                    )),
                    BroadwayDateTime::fromString('1970-01-01T00:00:00.000')
                ),
                $this->createExpectedAuditLogEntry(
                    new IdentityId('0123'),
                    new Institution('4567'),
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    new SecondFactorId('ijkl'),
                    new SecondFactorType('sms'),
                    'Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event\EventStub',
                    new StepupDateTime(new CoreDateTime('1970-01-01T00:00:00.000')),
                    self::$actorCommonName
                )
            ],
        ];
    }

    /**
     * @test
     * @group api-projector
     * @dataProvider auditable_events
     *
     * @param DomainMessage $message
     * @param AuditLogEntry $expectedEntry
     */
    public function it_creates_entries_for_auditable_events(DomainMessage $message, AuditLogEntry $expectedEntry)
    {
        $repository = m::mock('Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository');
        $repository->shouldReceive('save')->once()->with(self::spy($actualEntry));

        $identityRepository = m::mock('Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository');

        $identity             = new Identity();
        $identity->commonName = self::$actorCommonName;
        $identityRepository->shouldReceive('find')->between(0, 1)->andReturn($identity);

        $projector = new AuditLogProjector($repository, $identityRepository);
        $projector->handle($message);

        // we are not concerned about matching the UUID generated by the auditlogprojector
        $expectedEntry->id = $actualEntry->id;

        // PHPUnit's comparison is more informative than Mockery's no-match exception.
        $this->assertEquals($expectedEntry, $actualEntry);
    }

    private function createAuditLogMetadata(
        IdentityId $identityId,
        Institution $institution,
        SecondFactorId $secondFactorId = null,
        SecondFactorType $secondFactorType = null
    ) {
        $metadata = new Metadata();
        $metadata->identityId = $identityId;
        $metadata->identityInstitution = $institution;
        $metadata->secondFactorId = $secondFactorId;
        $metadata->secondFactorType = $secondFactorType;

        return $metadata;
    }

    private function createExpectedAuditLogEntry(
        IdentityId $actorId = null,
        Institution $actorInstitution = null,
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId = null,
        SecondFactorType $secondFactorType = null,
        $event,
        StepupDateTime $recordedOn,
        $actorCommonName = null
    ) {
        $entry = new AuditLogEntry();
        $entry->actorId = $actorId ? (string) $actorId : null;
        $entry->actorInstitution = $actorInstitution ? (string) $actorInstitution : null;
        $entry->identityId = (string) $identityId;
        $entry->identityInstitution = $identityInstitution;
        $entry->secondFactorId = $secondFactorId ? (string) $secondFactorId : null;
        $entry->secondFactorType = $secondFactorType ? (string) $secondFactorType : null;
        $entry->event = $event;
        $entry->recordedOn = $recordedOn;
        $entry->actorCommonName = $actorCommonName;

        return $entry;
    }

    /**
     * @param mixed &$spy
     * @return \Mockery\Matcher\MatcherAbstract
     */
    private static function spy(&$spy)
    {
        return m::on(
            function ($value) use (&$spy) {
                $spy = $value;

                return true;
            }
        );
    }
}
