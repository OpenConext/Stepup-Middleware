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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Matcher\MatcherAbstract;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\DateTime\DateTime as StepupDateTime;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Projector\AuditLogProjector;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Projector\Event\EventStub;

final class AuditLogProjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private static string $actorCommonName = 'Actor CommonName';

    public function auditable_events(): array
    {
        return [
            'no actor, with second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata(),
                    new EventStub(
                        $this->createAuditLogMetadata(
                            new IdentityId('abcd'),
                            new Institution('efgh'),
                            new SecondFactorId('ijkl'),
                            new SecondFactorType('yubikey'),
                            new YubikeyPublicId('99992222'),
                        ),
                    ),
                    BroadwayDateTime::fromString('1970-01-01H00:00:00.000'),
                ),
                $this->createExpectedAuditLogEntry(
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    EventStub::class,
                    new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000')),
                    null,
                    null,
                    new SecondFactorId('ijkl'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('99992222'),
                ),
            ],
            'no actor, without second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata(),
                    new EventStub(
                        $this->createAuditLogMetadata(
                            new IdentityId('abcd'),
                            new Institution('efgh'),
                        ),
                    ),
                    BroadwayDateTime::fromString('1970-01-01H00:00:00.000'),
                ),
                $this->createExpectedAuditLogEntry(
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    EventStub::class,
                    new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000')),
                ),
            ],
            'with actor, with second factor' => [
                new DomainMessage(
                    'id',
                    0,
                    new MessageMetadata([
                        'actorId' => '0123',
                        'actorInstitution' => '4567',
                    ]),
                    new EventStub(
                        $this->createAuditLogMetadata(
                            new IdentityId('abcd'),
                            new Institution('efgh'),
                            new SecondFactorId('ijkl'),
                            new SecondFactorType('yubikey'),
                            new YubikeyPublicId('99992222'),
                        ),
                    ),
                    BroadwayDateTime::fromString('1970-01-01H00:00:00.000'),
                ),
                $this->createExpectedAuditLogEntry(
                    new IdentityId('abcd'),
                    new Institution('efgh'),
                    EventStub::class,
                    new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000')),
                    new IdentityId('0123'),
                    new Institution('4567'),
                    new SecondFactorId('ijkl'),
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('99992222'),
                    new CommonName(self::$actorCommonName),
                ),
            ],
        ];
    }

    /**
     * @test
     * @group api-projector
     * @dataProvider auditable_events
     */
    public function it_creates_entries_for_auditable_events(DomainMessage $message, AuditLogEntry $expectedEntry): void
    {
        $repository = m::mock(AuditLogRepository::class);
        $actualEntry = null;
        $repository->shouldReceive('save')->with($this->spy($actualEntry));
        /** @var null|AuditLogEntry $actualEntry */

        $identityRepository = m::mock(IdentityRepository::class);

        $identity = new Identity();
        $identity->commonName = new CommonName(self::$actorCommonName);
        $identityRepository->shouldReceive('find')->andReturn($identity);

        $projector = new AuditLogProjector($repository, $identityRepository);
        $projector->handle($message);

        // we are not concerned about matching the UUID generated by the auditlogprojector
        if($actualEntry !== null) {
            $expectedEntry->id = $actualEntry->id;
        }else{
            $expectedEntry->id = '';
        }

        // PHPUnit's comparison is more informative than Mockery's no-match exception.
        $this->assertEquals($expectedEntry, $actualEntry);
    }

    private function createAuditLogMetadata(
        IdentityId $identityId,
        Institution $institution,
        SecondFactorId $secondFactorId = null,
        SecondFactorType $secondFactorType = null,
        SecondFactorIdentifier $secondFactorIdentifier = null,
    ): Metadata {
        $metadata = new Metadata();
        $metadata->identityId = $identityId;
        $metadata->identityInstitution = $institution;
        $metadata->secondFactorId = $secondFactorId;
        $metadata->secondFactorType = $secondFactorType;
        $metadata->secondFactorIdentifier = $secondFactorIdentifier;

        return $metadata;
    }

    private function createExpectedAuditLogEntry(
        IdentityId $identityId,
        Institution $identityInstitution,
        string $event,
        StepupDateTime $recordedOn,
        IdentityId $actorId = null,
        Institution $actorInstitution = null,
        SecondFactorId $secondFactorId = null,
        SecondFactorType $secondFactorType = null,
        ?YubikeyPublicId $secondFactorIdentifier = null,
        ?CommonName $actorCommonName = null,
    ): AuditLogEntry {
        $entry = new AuditLogEntry();
        $entry->actorId = $actorId instanceof IdentityId ? $actorId : null;
        $entry->actorInstitution = $actorInstitution instanceof Institution ? $actorInstitution : null;
        $entry->identityId = $identityId;
        $entry->identityInstitution = $identityInstitution;
        $entry->secondFactorId = $secondFactorId instanceof SecondFactorId ? $secondFactorId : null;
        $entry->secondFactorType = $secondFactorType instanceof SecondFactorType ? $secondFactorType : null;
        $entry->secondFactorIdentifier = $secondFactorIdentifier instanceof YubikeyPublicId ? $secondFactorIdentifier : null;
        $entry->event = $event;
        $entry->recordedOn = $recordedOn;
        $entry->actorCommonName = $actorCommonName;

        return $entry;
    }

    /**
     * @return MatcherAbstract
     */
    private function spy(mixed &$spy): MatcherAbstract
    {
        return m::on(
            function ($value) use (&$spy): bool {
                $spy = $value;

                return true;
            },
        );
    }
}
