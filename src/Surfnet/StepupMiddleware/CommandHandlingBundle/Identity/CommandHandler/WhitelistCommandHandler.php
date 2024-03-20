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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository as RepositoryInterface;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Whitelist;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AddToWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RemoveFromWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ReplaceWhitelistCommand;

class WhitelistCommandHandler extends SimpleCommandHandler
{
    public function __construct(
        private readonly RepositoryInterface $repository,
    ) {
    }

    public function handleReplaceWhitelistCommand(ReplaceWhitelistCommand $command): void
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutions);
        $whitelist->replaceAll(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    public function handleAddToWhitelistCommand(AddToWhitelistCommand $command): void
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutionsToBeAdded);
        $whitelist->add(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    public function handleRemoveFromWhitelistCommand(RemoveFromWhitelistCommand $command): void
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutionsToBeRemoved);
        $whitelist->remove(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    private function getWhitelist(): Whitelist
    {
        try {
            $whitelist = $this->repository->load(Whitelist::WHITELIST_AGGREGATE_ID);
            assert($whitelist instanceof Whitelist);
            return $whitelist;
        } catch (AggregateNotFoundException) {
            return Whitelist::create(new InstitutionCollection());
        }
    }

    /**
     * @return Institution[]
     */
    private function mapArrayToInstitutions(array $institutions): array
    {
        return array_map(fn($institutionName): Institution => new Institution($institutionName), $institutions);
    }
}
