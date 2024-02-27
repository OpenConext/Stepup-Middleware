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
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) - Events, events, events!
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaListingProjector extends Projector
{
    private RaListingRepository $raListingRepository;

    private IdentityRepository $identityRepository;

    public function __construct(RaListingRepository $raListingRepository, IdentityRepository $identityRepository)
    {
        $this->raListingRepository = $raListingRepository;
        $this->identityRepository = $identityRepository;
    }

    /**
     * @param IdentityAccreditedAsRaForInstitutionEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaForInstitutionEvent(IdentityAccreditedAsRaForInstitutionEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation,
            $event->raInstitution
        );

        $this->raListingRepository->save($raListing);
    }

    /**
     * @param IdentityAccreditedAsRaaForInstitutionEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaForInstitutionEvent(IdentityAccreditedAsRaaForInstitutionEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation,
            $event->raInstitution
        );

        $this->raListingRepository->save($raListing);
    }

    public function applyRegistrationAuthorityInformationAmendedForInstitutionEvent(
        RegistrationAuthorityInformationAmendedForInstitutionEvent $event
    ): void {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);

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

    public function applyAppointedAsRaForInstitutionEvent(AppointedAsRaForInstitutionEvent $event): void
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);

        $raListing->role = AuthorityRole::ra();

        $this->raListingRepository->save($raListing);
    }

    public function applyAppointedAsRaaForInstitutionEvent(AppointedAsRaaForInstitutionEvent $event): void
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);

        $raListing->role = AuthorityRole::raa();

        $this->raListingRepository->save($raListing);
    }

    public function applyRegistrationAuthorityRetractedForInstitutionEvent(RegistrationAuthorityRetractedForInstitutionEvent $event): void
    {
        $this->raListingRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);
    }


    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->raListingRepository->removeByIdentityId($event->identityId, $event->identityInstitution);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation,
            $event->identityInstitution
        );

        $this->raListingRepository->save($raListing);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event): void
    {
        $identity = $this->identityRepository->find((string) $event->identityId);

        $raListing = RaListing::create(
            (string) $event->identityId,
            $event->identityInstitution,
            $identity->commonName,
            $identity->email,
            AuthorityRole::fromRegistrationAuthorityRole($event->registrationAuthorityRole),
            $event->location,
            $event->contactInformation,
            $event->identityInstitution
        );

        $this->raListingRepository->save($raListing);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param RegistrationAuthorityInformationAmendedEvent $event
     */
    public function applyRegistrationAuthorityInformationAmendedEvent(
        RegistrationAuthorityInformationAmendedEvent $event
    ): void {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndRaInstitution($event->identityId, $event->identityInstitution);

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

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaEvent $event
     */
    public function applyAppointedAsRaEvent(AppointedAsRaEvent $event): void
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndInstitution($event->identityId, $event->identityInstitution);

        $raListing->role = AuthorityRole::ra();

        $this->raListingRepository->save($raListing);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param AppointedAsRaaEvent $event
     */
    public function applyAppointedAsRaaEvent(AppointedAsRaaEvent $event): void
    {
        /** @var RaListing $raListing */
        $raListing = $this->raListingRepository->findByIdentityIdAndInstitution($event->identityId, $event->identityInstitution);

        $raListing->role = AuthorityRole::raa();

        $this->raListingRepository->save($raListing);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param RegistrationAuthorityRetractedEvent $event
     */
    public function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event): void
    {
        $this->raListingRepository->removeByIdentityIdAndInstitution($event->identityId, $event->identityInstitution);
    }
}
