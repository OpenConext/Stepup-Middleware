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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\Event\RaaUpdatedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Raa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository;

class RaaProjector extends Projector
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaaRepository
     */
    private $raaRepository;

    public function __construct(RaaRepository $raaRepository)
    {
        $this->raaRepository = $raaRepository;
    }

    public function updateRaaConfiguration(RaaUpdatedEvent $event)
    {
        foreach ($event->raas as $institution => $raaList) {
            $this->updateRaaListForInstitution($institution, $raaList);
        }
    }

    private function updateRaaListForInstitution($institution, $raaList)
    {
        $existingNameIds = $this->raaRepository->getAllNameIdsRegisteredFor($institution);
        $newNameIds = array_map(function ($raa) {
            return $raa['name_id'];
        }, $raaList);

        $toBeInsertedNameIds = array_diff($newNameIds, $existingNameIds);
        $toBeInserted = array_filter($raaList, function ($raa) use ($toBeInsertedNameIds) {
            return in_array($raa['name_id'], $toBeInsertedNameIds);
        });

        $raaCollection = [];
        foreach ($toBeInserted as $raaConfiguration) {
            $raa = Raa::create($institution, $raaConfiguration['name_id']);
            $raa->location = $raaConfiguration['location'];
            $raa->contactInformation = $raaConfiguration['contact_info'];

            $raaCollection[] = $raa;
        }

        $this->raaRepository->saveAll($raaCollection);
    }
}
