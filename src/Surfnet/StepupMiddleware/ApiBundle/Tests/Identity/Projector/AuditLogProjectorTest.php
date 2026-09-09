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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\DateTime\DateTime as StepupDateTime;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
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

    #[Test]
    #[DataProvider('auditable_events')]
    #[Group('api-projector')]
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
        if ($actualEntry !== null) {
            $expectedEntry->id = $actualEntry->id;
        } else {
            $expectedEntry->id = '';
        }

        // PHPUnit's comparison is more informative than Mockery's no-match exception.
        $this->assertEquals($expectedEntry, $actualEntry);
    }

    #[Test]
    #[Group('api-projector')]
    public function it_creates_a_deprovisioned_entry_and_anonymizes_the_identitys_other_entries(): void
    {
        $identityId = new IdentityId('abcd');
        $institution = new Institution('efgh');

        $existingEntry = new AuditLogEntry();
        $existingEntry->id = 'existing-entry';
        $existingEntry->identityId = $identityId;
        $existingEntry->identityInstitution = $institution;
        $existingEntry->actorCommonName = new CommonName(self::$actorCommonName);
        $existingEntry->event = 'SomeEarlierEvent';
        $existingEntry->recordedOn = new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000'));

        $entryWhereIdentityIsActor = new AuditLogEntry();
        $entryWhereIdentityIsActor->id = 'actor-entry';
        $entryWhereIdentityIsActor->identityId = new IdentityId('some-other-identity');
        $entryWhereIdentityIsActor->identityInstitution = $institution;
        $entryWhereIdentityIsActor->actorId = $identityId;
        $entryWhereIdentityIsActor->actorCommonName = new CommonName(self::$actorCommonName);
        $entryWhereIdentityIsActor->event = 'SomeEarlierEvent';
        $entryWhereIdentityIsActor->recordedOn = new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000'));

        $repository = m::mock(AuditLogRepository::class);

        $newEntry = null;
        $repository->shouldReceive('find')
            ->once()
            ->with(AuditLogEntry::deprovisionedEntryIdFor('id', 0))
            ->andReturnNull();
        $repository->shouldReceive('save')->once()->with($this->spy($newEntry));
        /** @var null|AuditLogEntry $newEntry */

        // The new entry is flushed (AuditLogRepository::save() flushes immediately) before this is
        // called, so a real findByIdentityId() re-query picks it up alongside the pre-existing entry.
        $repository->shouldReceive('findByIdentityId')->once()->with($identityId)
            ->andReturnUsing(function () use ($existingEntry, &$newEntry): array {
                return [$existingEntry, $newEntry];
            });
        $repository->shouldReceive('findEntriesWhereIdentityIsActorOnly')->once()->with($identityId)
            ->andReturn([$entryWhereIdentityIsActor]);
        $repository->shouldReceive('saveAll')->once()->with(
            m::on(function (array $entries) use ($existingEntry, &$newEntry): bool {
                return $entries === [$existingEntry, $newEntry];
            }),
        );
        $repository->shouldReceive('saveAll')->once()->with([$entryWhereIdentityIsActor]);

        $identityRepository = m::mock(IdentityRepository::class);

        $projector = new AuditLogProjector($repository, $identityRepository);

        $message = new DomainMessage(
            'id',
            0,
            new MessageMetadata(),
            new IdentityForgottenEvent($identityId, $institution),
            BroadwayDateTime::fromString('1970-01-01H00:00:00.000'),
        );

        $projector->handle($message);

        // A new "deprovisioned" audit log entry must have been created for the identity.
        $this->assertNotNull($newEntry);
        $this->assertSame((string)$identityId, $newEntry->identityId);
        $this->assertSame($institution, $newEntry->identityInstitution);
        $this->assertSame(IdentityForgottenEvent::class, $newEntry->event);

        // Pre-existing entries for the identity must still be anonymized, same as before this change.
        $this->assertEquals(CommonName::unknown(), $existingEntry->actorCommonName);
        $this->assertEquals(CommonName::unknown(), $entryWhereIdentityIsActor->actorCommonName);

        // The new "deprovisioned" entry is swept up by the same anonymization pass, since it belongs
        // to the identity being forgotten.
        $this->assertEquals(CommonName::unknown(), $newEntry->actorCommonName);
    }

    #[Test]
    #[Group('api-projector')]
    public function it_creates_two_distinct_deprovisioned_entries_that_share_a_second(): void
    {
        $identityId = new IdentityId('abcd');
        $institution = new Institution('efgh');

        $firstEntryId = AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 3);
        $secondEntryId = AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 7);

        $savedEntries = [];
        $repository = m::mock(AuditLogRepository::class);
        $repository->shouldReceive('find')->once()->with($firstEntryId)->andReturnNull();
        $repository->shouldReceive('find')->once()->with($secondEntryId)->andReturnNull();
        $repository->shouldReceive('save')->twice()->with(
            m::on(function (AuditLogEntry $entry) use (&$savedEntries): bool {
                $savedEntries[] = $entry;

                return true;
            }),
        );
        $repository->shouldReceive('findByIdentityId')->twice()->with($identityId)
            ->andReturnUsing(function () use (&$savedEntries): array {
                return $savedEntries;
            });
        $repository->shouldReceive('findEntriesWhereIdentityIsActorOnly')->twice()->with($identityId)->andReturn([]);
        $repository->shouldReceive('saveAll')->twice()->with(
            m::on(function (array $entries) use (&$savedEntries): bool {
                if (count($entries) !== count($savedEntries)) {
                    return false;
                }

                return array_map(fn(AuditLogEntry $entry): string => $entry->id, $entries)
                    === array_map(fn(AuditLogEntry $entry): string => $entry->id, $savedEntries);
            }),
        );
        $repository->shouldReceive('saveAll')->twice()->with([]);

        $identityRepository = m::mock(IdentityRepository::class);

        $projector = new AuditLogProjector($repository, $identityRepository);
        $projector->handle(new DomainMessage(
            'abcd',
            3,
            new MessageMetadata(),
            new IdentityForgottenEvent($identityId, $institution),
            BroadwayDateTime::fromString('1970-01-01T00:00:00.100000'),
        ));
        $projector->handle(new DomainMessage(
            'abcd',
            7,
            new MessageMetadata(),
            new IdentityForgottenEvent($identityId, $institution),
            BroadwayDateTime::fromString('1970-01-01T00:00:00.900000'),
        ));

        $this->assertCount(2, $savedEntries);
        $this->assertSame($firstEntryId, $savedEntries[0]->id);
        $this->assertSame($secondEntryId, $savedEntries[1]->id);
    }

    #[Test]
    #[Group('api-projector')]
    public function it_skips_creating_a_duplicate_deprovisioned_entry_but_still_anonymizes_existing_entries(): void
    {
        $identityId = new IdentityId('abcd');
        $institution = new Institution('efgh');
        $recordedOn = new StepupDateTime(new CoreDateTime('1970-01-01H00:00:00.000'));

        $existingEntry = new AuditLogEntry();
        $existingEntry->id = 'existing-entry';
        $existingEntry->identityId = $identityId;
        $existingEntry->identityInstitution = $institution;
        $existingEntry->actorCommonName = new CommonName(self::$actorCommonName);
        $existingEntry->event = IdentityForgottenEvent::class;
        $existingEntry->recordedOn = $recordedOn;

        $entryWhereIdentityIsActor = new AuditLogEntry();
        $entryWhereIdentityIsActor->id = 'actor-entry';
        $entryWhereIdentityIsActor->identityId = new IdentityId('some-other-identity');
        $entryWhereIdentityIsActor->identityInstitution = $institution;
        $entryWhereIdentityIsActor->actorId = $identityId;
        $entryWhereIdentityIsActor->actorCommonName = new CommonName(self::$actorCommonName);
        $entryWhereIdentityIsActor->event = 'SomeEarlierEvent';
        $entryWhereIdentityIsActor->recordedOn = $recordedOn;

        $repository = m::mock(AuditLogRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(AuditLogEntry::deprovisionedEntryIdFor('id', 0))
            ->andReturn($existingEntry);
        $repository->shouldNotReceive('save');
        $repository->shouldReceive('findByIdentityId')->once()->with($identityId)->andReturn([$existingEntry]);
        $repository->shouldReceive('findEntriesWhereIdentityIsActorOnly')->once()->with($identityId)
            ->andReturn([$entryWhereIdentityIsActor]);
        $repository->shouldReceive('saveAll')->once()->with([$existingEntry]);
        $repository->shouldReceive('saveAll')->once()->with([$entryWhereIdentityIsActor]);

        $identityRepository = m::mock(IdentityRepository::class);

        $projector = new AuditLogProjector($repository, $identityRepository);
        $projector->handle(new DomainMessage(
            'id',
            0,
            new MessageMetadata(),
            new IdentityForgottenEvent($identityId, $institution),
            BroadwayDateTime::fromString('1970-01-01H00:00:00.000'),
        ));

        $this->assertEquals(CommonName::unknown(), $existingEntry->actorCommonName);
        $this->assertEquals(CommonName::unknown(), $entryWhereIdentityIsActor->actorCommonName);
    }

    private function createAuditLogMetadata(
        IdentityId $identityId,
        Institution $institution,
        ?SecondFactorId $secondFactorId = null,
        ?SecondFactorType $secondFactorType = null,
        ?SecondFactorIdentifier $secondFactorIdentifier = null,
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
        ?IdentityId $actorId = null,
        ?Institution $actorInstitution = null,
        ?SecondFactorId $secondFactorId = null,
        ?SecondFactorType $secondFactorType = null,
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
