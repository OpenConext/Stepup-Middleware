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

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Whitelist;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AddToWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RemoveFromWhitelistCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ReplaceWhitelistCommand;

class WhitelistCommandHandler extends CommandHandler
{
    /**
     * @var \Surfnet\Stepup\Identity\EventSourcing\WhitelistRepository
     */
    private $repository;

    /**
     * @param RepositoryInterface  $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param ReplaceWhitelistCommand $command
     */
    public function handleReplaceWhitelistCommand(ReplaceWhitelistCommand $command)
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutions);
        $whitelist->replaceAll(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    /**
     * @param AddToWhitelistCommand $command
     */
    public function handleAddToWhitelistCommand(AddToWhitelistCommand $command)
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutionsToBeAdded);
        $whitelist->add(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    /**
     * @param RemoveFromWhitelistCommand $command
     */
    public function handleRemoveFromWhitelistCommand(RemoveFromWhitelistCommand $command)
    {
        $whitelist = $this->getWhitelist();

        $institutions = $this->mapArrayToInstitutions($command->institutionsToBeRemoved);
        $whitelist->remove(new InstitutionCollection($institutions));

        $this->repository->save($whitelist);
    }

    /**
     * @return Whitelist
     */
    private function getWhitelist()
    {
        try {
            return $this->repository->load(Whitelist::WHITELIST_AGGREGATE_ID);
        } catch (AggregateNotFoundException $e) {
            return Whitelist::create(new InstitutionCollection());
        }
    }

    /**
     * @param array $institutions
     * @return Institution[]
     */
    private function mapArrayToInstitutions(array $institutions)
    {
        return array_map(function ($institutionName) {
            return new Institution($institutionName);
        }, $institutions);
    }
}
