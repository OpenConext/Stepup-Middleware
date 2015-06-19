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
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) - Events, events, events!
 */
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

    /**
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation
        );

        $this->raListingRepository->save($raListing);
    }

    /**
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation
        );

        $this->raListingRepository->save($raListing);
    }

    public function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event
    ) {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->find($event->identityId);

        if (!$raListing) {
            throw new RuntimeException(
                "Tried to amend an RaListing's registration authority location and contact information, " .
                "but the listing could not be found"
            );
        }

        $raListing->location = $event->location;
        $raListing->contactInformation = $event->contactInformation;

        $this->raListingRepository->save($raListing);
    }

    public function applyAppointedAsRaEvent(AppointedAsRaEvent $event)
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->find($event->identityId);

        $raListing->role = AuthorityRole::ra();

        $this->raListingRepository->save($raListing);
    }

    public function applyAppointedAsRaaEvent(AppointedAsRaaEvent $event)
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->find($event->identityId);

        $raListing->role = AuthorityRole::raa();

        $this->raListingRepository->save($raListing);
    }

    public function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->find($event->identityId);

        $this->raListingRepository->remove($raListing);
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->raListingRepository->removeByIdentityId($event->identityId);
    }
}
