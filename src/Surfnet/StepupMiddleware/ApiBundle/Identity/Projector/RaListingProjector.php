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
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

class RaListingProjector extends Projector
{
    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(RaListingRepository $raListingRepository, IdentityRepository $identityRepository)
    {
        $this->raListingRepository = $raListingRepository;
        $this->identityRepository = $identityRepository;
    }

    public function applyRaaUpdatedEvent(RaaUpdatedEvent $raaUpdatedEvent)
    {
        foreach ($raaUpdatedEvent->raas as $institution => $raaListings) {
            $this->updateRaaListingsForInstitution($institution, $raaListings);
        }
    }

    private function updateRaaListingsForInstitution($institution, $raaListings)
    {
        $nameIds = array_map(function ($raa) {
            return $raa['name_id'];
        }, $raaListings);

        $existingRaListings = $this->raListingRepository->getRaasByInstitution($institution);
        $identities         = $this->identityRepository->findByNameIdsIndexed($nameIds);

        if (count($identities) !== count($nameIds)) {
            $invalid = [];
            foreach ($nameIds as $nameId) {
                if (!array_key_exists($nameId, $identities)) {
                    $invalid[] = $nameId;
                }
            }

            throw new RuntimeException(sprintf(
                'Cannot create RaListings as the RAAs with the following NameIDs have no corresponding Identity: "%s"',
                implode('", "', $invalid)
            ));
        }

        $existingByIdentityId = $existingRaListings
            ->map(function (RaListing $raListing) {
                return $raListing->identityId;
            })
            ->toArray();

        $existingByIdentity = array_intersect_key($identities, array_flip($existingByIdentityId));
        $toInsert = array_filter($raaListings, function ($raa) use ($existingByIdentity) {
            return !array_key_exists($raa['name_id'], $existingByIdentity);
        });

        $listingsToSave = [];
        foreach ($toInsert as $newRaListing) {
            $identity = $identities[$newRaListing['name_id']];

            $listingsToSave[] = RaListing::create(
                $identity->id,
                $identity->institution,
                $identity->commonName,
                $identity->email,
                AuthorityRole::RAA(),
                $newRaListing['location'],
                $newRaListing['contact_info']
            );
        }

        $this->raListingRepository->saveAll($listingsToSave);

        unset($listingsToSave, $toInsert, $existingByIdentity, $existingByIdentityId, $existingRaListings, $identities);
    }
}
