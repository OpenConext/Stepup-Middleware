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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Broadway\Domain\DomainMessage;
use DateTime as CoreDateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

/**
 * Deprovisioning (IdentityForgottenEvent) was added to the audit log after the fact. Identities that
 * were forgotten before that change have no "deprovisioned" entry. This command creates the missing
 * entries straight from the event store, without replaying the event through AuditLogProjector - that
 * would also re-run the destructive anonymisation pass over the identity's (possibly since restored)
 * current audit log.
 *
 * It is idempotent: an entry that already exists for the identity + moment is left untouched, so it is
 * safe to run more than once. All writes happen in a single transaction, so an interrupted run rolls
 * back completely and leaves nothing half-applied.
 *
 * Assumptions:
 *  - It is run with the lifecycle (deprovisioning) API access disabled. The existence check and the
 *    insert are not atomic with AuditLogProjector, so a deprovisioning projected live during the run
 *    could be recorded twice. The historical IdentityForgottenEvents this command targets are not
 *    re-emitted by anything, so the only way to hit this is to deprovision an identity, or run an
 *    event replay, while the backfill is running.
 *  - An identity is never forgotten twice within the same wall-clock second. A restore
 *    (UpdateIdentityCommand -> Identity::restore()) has to happen between two forgets, so the existence
 *    check keying on identity + event + second-precision recordedOn uniquely identifies one
 *    deprovisioning. In-memory the events are still distinguished by playhead, so two same-second
 *    events in one run are both created.
 *
 * The whole set of IdentityForgottenEvents is read up front (it is bounded by the number of identities
 * ever deprovisioned); the resulting audit log entries are written in batches with the entity manager
 * cleared between them.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[AsCommand(
    name: 'stepup:audit-log:backfill-deprovisioned',
    description: 'Creates the missing "deprovisioned" audit log entries for identities forgotten before '
    . 'the deprovisioning action was recorded. Idempotent.'
)]
final class BackfillDeprovisionedAuditLogEntriesCommand
{
    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly DBALEventHydrator $eventHydrator,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Report what would be created without writing anything', name: 'dry-run')]
        bool $dryRun = false,
        #[Option(description: 'Skip the confirmation prompt', name: 'force')]
        bool $force = false,
    ): int {
        if ($this->shouldAbort($input, $output, $dryRun, $force)) {
            return 1;
        }

        $occurrences = $this->collectDeprovisioningOccurrences();
        $missing = $this->findMissingOccurrences($occurrences);

        $this->reportMissingOccurrences($output, $missing, $dryRun);

        return $this->finishBackfill($output, $occurrences, $missing, $dryRun);
    }

    /**
     * @return array<string, array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}>
     */
    private function collectDeprovisioningOccurrences(): array
    {
        // Phase 1: every distinct deprovisioning occurrence, keyed by aggregate id + playhead so a
        // second IdentityForgottenEvent (after a restore) is never merged with the first.
        $eventStreamType = strtr(IdentityForgottenEvent::class, '\\', '.');
        $events = $this->eventHydrator->fetchByEventTypes([$eventStreamType]);

        $occurrences = [];

        foreach ($events->getIterator() as $domainMessage) {
            /** @var DomainMessage $domainMessage */
            $event = $domainMessage->getPayload();

            if (!$event instanceof IdentityForgottenEvent) {
                continue;
            }

            $key = $domainMessage->getId() . '|' . $domainMessage->getPlayhead();
            $occurrences[$key] = [
                'identityId' => $event->identityId,
                'institution' => $event->identityInstitution,
                'recordedOn' => new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString())),
            ];
        }

        return $occurrences;
    }

    private function shouldAbort(
        InputInterface $input,
        OutputInterface $output,
        bool $dryRun,
        bool $force,
    ): bool {
        if ($dryRun || $force || !$input->isInteractive()) {
            return false;
        }

        $question = new ConfirmationQuestion(
            '<question>Run this only with the lifecycle (deprovisioning) API access disabled, to avoid '
            . 'duplicate entries from concurrent live projection. Continue? (y/N)</question> ',
            false,
        );

        if ((new QuestionHelper())->ask($input, $output, $question)) {
            return false;
        }

        $output->writeln('<comment>Aborted.</comment>');

        return true;
    }

    /**
     * @param array<string, array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $occurrences
     * @return list<array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}>
     */
    private function findMissingOccurrences(array $occurrences): array
    {
        // Phase 2: keep only the occurrences that have no audit log entry yet. All lookups happen
        // before any insert, so two occurrences that fall in the same second are both kept.
        return array_values(array_filter(
            $occurrences,
            fn(array $occurrence): bool => !$this->auditLogRepository->hasDeprovisionedEntry(
                $occurrence['identityId'],
                IdentityForgottenEvent::class,
                $occurrence['recordedOn'],
            ),
        ));
    }

    /**
     * @param list<array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $missing
     */
    private function reportMissingOccurrences(OutputInterface $output, array $missing, bool $dryRun): void
    {
        foreach ($missing as $occurrence) {
            $output->writeln(
                sprintf(
                    '<info>%s deprovisioned entry for identity %s (%s) recorded on %s</info>',
                    $dryRun ? 'Would create' : 'Creating',
                    $occurrence['identityId'],
                    $occurrence['institution'],
                    $occurrence['recordedOn']->format(DateTime::FORMAT),
                ),
                OutputInterface::VERBOSITY_VERBOSE,
            );
        }
    }

    /**
     * @param array<string, array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $occurrences
     * @param list<array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $missing
     */
    private function finishBackfill(
        OutputInterface $output,
        array $occurrences,
        array $missing,
        bool $dryRun,
    ): int {
        if (!$this->persistMissingOccurrences($output, $missing, $dryRun)) {
            return 1;
        }

        $output->writeln($this->summary($occurrences, $missing, $dryRun));

        return 0;
    }

    /**
     * @param list<array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $missing
     */
    private function persistMissingOccurrences(OutputInterface $output, array $missing, bool $dryRun): bool
    {
        if ($dryRun || $missing === []) {
            return true;
        }

        try {
            $this->persistInBatches($missing);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Backfill failed: %s</error>', $e->getMessage()));

            return false;
        }

        return true;
    }

    /**
     * @param array<string, array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $occurrences
     * @param list<array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $missing
     */
    private function summary(array $occurrences, array $missing, bool $dryRun): string
    {
        return sprintf(
            '<comment>%s: %d deprovisioned %s %s, %d already present</comment>',
            $dryRun ? 'Dry run' : 'Done',
            count($missing),
            count($missing) === 1 ? 'entry' : 'entries',
            $dryRun ? 'would be created' : 'created',
            count($occurrences) - count($missing),
        );
    }

    /**
     * @param array<int, array{identityId: IdentityId, institution: Institution, recordedOn: DateTime}> $missing
     */
    private function persistInBatches(array $missing): void
    {
        $entityManager = $this->entityManager();

        $entityManager->wrapInTransaction(static function () use ($entityManager, $missing): void {
            foreach (array_chunk($missing, self::BATCH_SIZE) as $chunk) {
                foreach ($chunk as $occurrence) {
                    $entry = new AuditLogEntry();
                    $entry->id = (string)Uuid::uuid4();
                    $entry->identityId = (string)$occurrence['identityId'];
                    $entry->identityInstitution = $occurrence['institution'];
                    $entry->actorCommonName = CommonName::unknown();
                    $entry->event = IdentityForgottenEvent::class;
                    $entry->recordedOn = $occurrence['recordedOn'];

                    $entityManager->persist($entry);
                }

                $entityManager->flush();
                $entityManager->clear();
            }
        });
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AuditLogEntry::class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException('No entity manager configured for AuditLogEntry');
        }

        return $manager;
    }
}
