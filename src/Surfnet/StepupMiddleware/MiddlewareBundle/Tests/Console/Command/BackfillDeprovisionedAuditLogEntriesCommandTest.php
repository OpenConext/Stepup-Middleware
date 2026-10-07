<?php

/**
 * Copyright 2025 SURFnet bv
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

declare(strict_types=1);

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\Console\Command;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\BackfillDeprovisionedAuditLogEntriesCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillDeprovisionedAuditLogEntriesCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DBALEventHydrator&MockInterface $eventHydrator;
    private AuditLogRepository&MockInterface $auditLogRepository;
    private EntityManagerInterface&MockInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->eventHydrator = m::mock(DBALEventHydrator::class);
        $this->auditLogRepository = m::mock(AuditLogRepository::class);
        $this->entityManager = m::mock(EntityManagerInterface::class);

        $registry = m::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManagerForClass')->with(AuditLogEntry::class)->andReturn($this->entityManager);
        $this->entityManager->shouldReceive('wrapInTransaction')->andReturnUsing(fn(callable $cb) => $cb());

        $this->commandTester = new CommandTester(
            new BackfillDeprovisionedAuditLogEntriesCommand($this->eventHydrator, $this->auditLogRepository, $registry),
        );
    }

    #[Test]
    public function it_creates_a_missing_entry_and_skips_one_that_already_exists(): void
    {
        $forgottenWithoutEntry = new IdentityId('11111111-1111-1111-1111-111111111111');
        $forgottenWithEntry = new IdentityId('22222222-2222-2222-2222-222222222222');
        $institution = new Institution('institution-a.example');

        $this->eventHydrator->shouldReceive('fetchByEventTypes')
            ->once()
            ->with(['Surfnet.Stepup.Identity.Event.IdentityForgottenEvent'])
            ->andReturn(new DomainEventStream([
                $this->forgottenMessage($forgottenWithoutEntry, $institution, 0, '2020-01-01T10:00:00.000000'),
                $this->forgottenMessage($forgottenWithEntry, $institution, 0, '2021-06-15T12:30:00.000000'),
            ]));

        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$forgottenWithoutEntry, 0))->andReturnNull();
        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$forgottenWithEntry, 0))->andReturn(new AuditLogEntry());

        $persisted = [];
        $this->entityManager->shouldReceive('persist')->once()
            ->with(m::on(function (AuditLogEntry $entry) use (&$persisted): bool {
                $persisted[] = $entry;
                return true;
            }));
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('clear')->once();

        $exitCode = $this->commandTester->execute(['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertCount(1, $persisted);
        $this->assertSame((string)$forgottenWithoutEntry, $persisted[0]->identityId);
        $this->assertSame($institution, $persisted[0]->identityInstitution);
        $this->assertSame(IdentityForgottenEvent::class, $persisted[0]->event);
        $this->assertStringContainsString('1 deprovisioned entry created, 1 already present', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_keeps_two_forgotten_events_in_the_same_second_as_distinct_entries(): void
    {
        $identityId = new IdentityId('55555555-5555-5555-5555-555555555555');
        $institution = new Institution('institution-d.example');

        // Forgotten, restored, forgotten again - two events, same second, different playheads.
        $this->eventHydrator->shouldReceive('fetchByEventTypes')->once()->andReturn(new DomainEventStream([
            $this->forgottenMessage($identityId, $institution, 3, '2020-01-01T10:00:00.100000'),
            $this->forgottenMessage($identityId, $institution, 7, '2020-01-01T10:00:00.900000'),
        ]));

        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 3))->andReturnNull();
        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 7))->andReturnNull();

        $this->entityManager->shouldReceive('persist')->twice();
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('clear')->once();

        $exitCode = $this->commandTester->execute(['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('2 deprovisioned entries created, 0 already present', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_creates_the_missing_same_second_occurrence_when_another_same_second_entry_already_exists(): void
    {
        $identityId = new IdentityId('77777777-7777-7777-7777-777777777777');
        $institution = new Institution('institution-f.example');

        $firstEntryId = AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 3);
        $secondEntryId = AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 7);

        $existingEntry = new AuditLogEntry();
        $existingEntry->id = $firstEntryId;

        $this->eventHydrator->shouldReceive('fetchByEventTypes')->once()->andReturn(new DomainEventStream([
            $this->forgottenMessage($identityId, $institution, 3, '2020-01-01T10:00:00.100000'),
            $this->forgottenMessage($identityId, $institution, 7, '2020-01-01T10:00:00.900000'),
        ]));

        $this->auditLogRepository->shouldReceive('find')->once()->with($firstEntryId)->andReturn($existingEntry);
        $this->auditLogRepository->shouldReceive('find')->once()->with($secondEntryId)->andReturnNull();

        $persisted = [];
        $this->entityManager->shouldReceive('persist')->once()
            ->with(m::on(function (AuditLogEntry $entry) use (&$persisted, $secondEntryId): bool {
                $persisted[] = $entry;

                return $entry->id === $secondEntryId;
            }));
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('clear')->once();

        $exitCode = $this->commandTester->execute(['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertCount(1, $persisted);
        $this->assertSame($secondEntryId, $persisted[0]->id);
        $this->assertStringContainsString('1 deprovisioned entry created, 1 already present', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_deduplicates_the_same_event_occurrence_seen_twice(): void
    {
        $identityId = new IdentityId('66666666-6666-6666-6666-666666666666');
        $institution = new Institution('institution-e.example');

        $message = $this->forgottenMessage($identityId, $institution, 2, '2020-02-02T11:11:11.000000');

        $this->eventHydrator->shouldReceive('fetchByEventTypes')->once()
            ->andReturn(new DomainEventStream([$message, $message]));

        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 2))->andReturnNull();

        $this->entityManager->shouldReceive('persist')->once();
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('clear')->once();

        $exitCode = $this->commandTester->execute(['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('1 deprovisioned entry created, 0 already present', $this->commandTester->getDisplay());
    }

    #[Test]
    public function dry_run_reports_the_same_count_it_would_write(): void
    {
        $identityId = new IdentityId('55555555-5555-5555-5555-555555555555');
        $institution = new Institution('institution-d.example');

        $this->eventHydrator->shouldReceive('fetchByEventTypes')->once()->andReturn(new DomainEventStream([
            $this->forgottenMessage($identityId, $institution, 3, '2020-01-01T10:00:00.100000'),
            $this->forgottenMessage($identityId, $institution, 7, '2020-01-01T10:00:00.900000'),
        ]));

        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 3))->andReturnNull();
        $this->auditLogRepository->shouldReceive('find')
            ->once()->with(AuditLogEntry::deprovisionedEntryIdFor((string)$identityId, 7))->andReturnNull();
        $this->entityManager->shouldNotReceive('persist');
        $this->entityManager->shouldNotReceive('flush');

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Dry run: 2 deprovisioned entries would be created', $this->commandTester->getDisplay());
    }

    #[Test]
    public function it_aborts_when_the_confirmation_is_declined(): void
    {
        $this->eventHydrator->shouldNotReceive('fetchByEventTypes');

        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Aborted.', $this->commandTester->getDisplay());
    }

    private function forgottenMessage(
        IdentityId $identityId,
        Institution $institution,
        int $playhead,
        string $recordedOn,
    ): DomainMessage {
        return new DomainMessage(
            (string)$identityId,
            $playhead,
            new Metadata([]),
            new IdentityForgottenEvent($identityId, $institution),
            BroadwayDateTime::fromString($recordedOn),
        );
    }
}
