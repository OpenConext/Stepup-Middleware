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

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsRemovedFromWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\Stepup\Identity\EventSourcing\WhitelistRepository;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Whitelist;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AddToWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RemoveFromWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ReplaceWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\WhitelistCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

class WhitelistCommandHandlerTest extends CommandHandlerTest
{
    /**
     * Shorthand for fixed Whitelist ID.
     */
    public const WID = Whitelist::WHITELIST_AGGREGATE_ID;

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new WhitelistCommandHandler(new WhitelistRepository($eventStore, $eventBus, $aggregateFactory));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function when_the_whitelist_does_not_exist_yet_it_is_created(): void
    {
        $command = new ReplaceWhitelistCommand();
        $command->institutions = ['Replace A', 'Replace B', 'Replace C'];
        $institutions = $this->mapStringValuesToInstitutions($command->institutions);

        $this->scenario
            ->when($command)
            ->then([
                new WhitelistCreatedEvent(new InstitutionCollection()),
                new WhitelistReplacedEvent(new InstitutionCollection($institutions)),
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function the_whitelist_can_be_fully_replaced(): void
    {
        $initialInstitutions = $this->mapStringValuesToInstitutions(['Initial One', 'Initial Two']);

        $command = new ReplaceWhitelistCommand();
        $command->institutions = ['Replace A', 'Replace B', 'Replace C'];

        $this->scenario
            ->withAggregateId(self::WID)
            ->given([new WhitelistCreatedEvent(new InstitutionCollection($initialInstitutions))])
            ->when($command)
            ->then([
                new WhitelistReplacedEvent(
                    new InstitutionCollection($this->mapStringValuesToInstitutions($command->institutions)),
                ),
            ]);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function an_institution_not_yet_on_the_whitelist_can_be_added_to_the_whitelist(): void
    {
        $initialInstitutions = $this->mapStringValuesToInstitutions(['Initial One', 'Initial Two']);

        $command = new AddToWhitelistCommand();
        $command->institutionsToBeAdded = ['Added Institution'];

        $this->scenario
            ->withAggregateId(self::WID)
            ->given([new WhitelistCreatedEvent(new InstitutionCollection($initialInstitutions))])
            ->when($command)
            ->then([
                new InstitutionsAddedToWhitelistEvent(
                    new InstitutionCollection($this->mapStringValuesToInstitutions($command->institutionsToBeAdded)),
                ),
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function an_institution_on_the_whitelist_may_not_be_added_again(): void
    {
        $this->expectExceptionMessage("Cannot add institution \"already exists\" as it is already whitelisted");
        $this->expectException(DomainException::class);

        $initialInstitutions = $this->mapStringValuesToInstitutions(['Initial One', 'Already Exists']);

        $command = new AddToWhitelistCommand();
        $command->institutionsToBeAdded = ['Already Exists'];

        $this->scenario
            ->withAggregateId(self::WID)
            ->given([new WhitelistCreatedEvent(new InstitutionCollection($initialInstitutions))])
            ->when($command);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function an_institution_on_the_whitelist_can_be_removed_from_the_whitelist(): void
    {
        $initialInstitutions = $this->mapStringValuesToInstitutions(['Initial One', 'On the whitelist']);

        $command = new RemoveFromWhitelistCommand();
        $command->institutionsToBeRemoved = ['On the whitelist'];

        $this->scenario
            ->withAggregateId(self::WID)
            ->given([new WhitelistCreatedEvent(new InstitutionCollection($initialInstitutions))])
            ->when($command)
            ->then([
                new InstitutionsRemovedFromWhitelistEvent(
                    new InstitutionCollection($this->mapStringValuesToInstitutions($command->institutionsToBeRemoved)),
                ),
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('command-handler')]
    #[\PHPUnit\Framework\Attributes\Group('whitelist')]
    public function an_institution_that_is_not_on_the_whitelist_cannot_be_removed(): void
    {
        $this->expectExceptionMessage("Cannot remove institution \"not on the whitelist\" as it is not whitelisted");
        $this->expectException(DomainException::class);
        $initialInstitutions = $this->mapStringValuesToInstitutions(['Initial One', 'Initial Two']);

        $command = new RemoveFromWhitelistCommand();
        $command->institutionsToBeRemoved = ['not on the whitelist'];

        $this->scenario
            ->withAggregateId(self::WID)
            ->given([new WhitelistCreatedEvent(new InstitutionCollection($initialInstitutions))])
            ->when($command);
    }

    /**
     * Helper function to quickly map String[] to Institution[]
     * @return list<Institution> $institutions
     */
    private function mapStringValuesToInstitutions(array $institutions): array
    {
        return array_values(array_map(static fn($institution): Institution => new Institution($institution), $institutions));
    }
}
