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
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\SelectRaaOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\SraaUpdatedEvent;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RaCandidateProjector extends Projector
{
    /**
     * @var RaCandidateRepository
     */
    private $raCandidateRepository;

    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    /**
     * @var institutionAuthorizationRepository
     */
    private $institutionAuthorizationRepository;
    /**
     * @var IdentityRepository
     */
    private $identityRepository;
    /**
     * @var RaSecondFactorRepository
     */
    private $raSecondFactorRepository;

    public function __construct(
        RaCandidateRepository $raCandidateRepository,
        RaListingRepository $raListingRepository,
        InstitutionAuthorizationRepository $institutionAuthorizationRepository,
        IdentityRepository $identityRepository,
        RaSecondFactorRepository $raSecondFactorRepository
    ) {
        $this->raCandidateRepository = $raCandidateRepository;
        $this->raListingRepository = $raListingRepository;
        $this->institutionAuthorizationRepository = $institutionAuthorizationRepository;
        $this->identityRepository = $identityRepository;
        $this->raSecondFactorRepository = $raSecondFactorRepository;
    }

    /**
     * @param VettedSecondFactorRevokedEvent $event
     * @return void
     */
    public function applyVettedSecondFactorRevokedEvent(VettedSecondFactorRevokedEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param CompliedWithVettedSecondFactorRevocationEvent $event
     * @return void
     */
    public function applyCompliedWithVettedSecondFactorRevocationEvent(
        CompliedWithVettedSecondFactorRevocationEvent $event
    ) {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    /**
     * @param SraaUpdatedEvent $event
     *
     * Removes all RaCandidates that have a nameId matching an SRAA, as they cannot be made RA(A) as they
     * already are SRAA.
     */
    public function applySraaUpdatedEvent(SraaUpdatedEvent $event)
    {
        $this->raCandidateRepository->removeByNameIds($event->sraaList);
    }

    /**
     * @param IdentityAccreditedAsRaForInstitutionEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaForInstitutionEvent(IdentityAccreditedAsRaForInstitutionEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);
    }

    /**
     * @param IdentityAccreditedAsRaaForInstitutionEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaForInstitutionEvent(IdentityAccreditedAsRaaForInstitutionEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->raInstitution);
    }

    /**
     * @param RegistrationAuthorityRetractedForInstitutionEvent $event
     * @return void
     */
    public function applyRegistrationAuthorityRetractedForInstitutionEvent(RegistrationAuthorityRetractedForInstitutionEvent $event)
    {
        $this->addCandidateToProjection(
            $event->identityInstitution,
            $event->identityId,
            $event->nameId,
            $event->commonName,
            $event->email
        );
    }

    protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityId($event->identityId);
    }

    protected function applySelectRaaOptionChangedEvent(SelectRaaOptionChangedEvent $event)
    {
        $authorizedInstitutions = $event->selectRaaOption->getInstitutions($event->institution);
        $this->updateInstitutionCandidatesFromCollection(new Institution($event->institution->getInstitution()), $authorizedInstitutions);
    }

    protected function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event)
    {
        $this->raCandidateRepository->removeByRaInstitution(new Institution($event->institution->getInstitution()));
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param IdentityAccreditedAsRaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaEvent(IdentityAccreditedAsRaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->identityInstitution);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param IdentityAccreditedAsRaaEvent $event
     * @return void
     */
    public function applyIdentityAccreditedAsRaaEvent(IdentityAccreditedAsRaaEvent $event)
    {
        $this->raCandidateRepository->removeByIdentityIdAndRaInstitution($event->identityId, $event->identityInstitution);
    }

    /**
     * This method is kept to be backwards compatible for changes before FGA
     *
     * @param RegistrationAuthorityRetractedEvent $event
     * @return void
     */
    public function applyRegistrationAuthorityRetractedEvent(RegistrationAuthorityRetractedEvent $event)
    {
        $candidate = RaCandidate::nominate(
            $event->identityId,
            $event->identityInstitution,
            $event->nameId,
            $event->commonName,
            $event->email,
            $event->identityInstitution
        );

        $this->raCandidateRepository->merge($candidate);
    }

    /**
     * @param Institution $institution
     * @param ConfigurationInstitution[] $authorizedInstitutions
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function updateInstitutionCandidatesFromCollection(Institution $institution, array $authorizedInstitutions)
    {
        // convert configuration to value institutions
        $raInstitutions = new InstitutionCollection();
        foreach ($authorizedInstitutions as $authorizedInstitution) {
            $raInstitutions->add(new Institution($authorizedInstitution->getInstitution()));
        }

        // Remove candidates from removed institutions
        $this->raCandidateRepository->removeInstitutionsNotInList($institution, $raInstitutions);

        // loop through authorized institutions
        foreach ($raInstitutions as $raInstitution) {
            // add new identities
            $raSecondFactors = $this->raSecondFactorRepository->findByInstitution($raInstitution->getIstitution());
            foreach ($raSecondFactors as $raSecondFactor) {
                $identity = $this->identityRepository->find($raSecondFactor->identityId);
                $identityId = new IdentityId($identity->id);

                // check if persistent in ra listing
                if ($this->raListingRepository->findByIdentityIdAndRaInstitution($identityId, $institution)) {
                    continue;
                }

                // create candidate if not exists
                $candidate = $this->raCandidateRepository->findByIdentityIdAndRaInstitution($identityId, $institution);
                if (!$candidate) {
                    $candidate = RaCandidate::nominate(
                        $identityId,
                        $identity->institution,
                        $identity->nameId,
                        $identity->commonName,
                        $identity->email,
                        $institution
                    );
                }

                // store
                $this->raCandidateRepository->merge($candidate);
            }
        }
    }

    /**
     * @param Institution $identityInstitution
     * @param IdentityId $identityId
     * @param NameId $identityNameId
     * @param CommonName $identityCommonName
     * @param Email $identityEmail
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function addCandidateToProjection(
        Institution $identityInstitution,
        IdentityId $identityId,
        NameId $identityNameId,
        CommonName $identityCommonName,
        Email $identityEmail
    ) {
        $institutionAuthorizations = $this->institutionAuthorizationRepository
            ->findAuthorizationOptionsForInstitution(new ConfigurationInstitution($identityInstitution->getInstitution()));

        $institutions = [];
        foreach ($institutionAuthorizations as $authorization) {
            $raInstitutionName = $authorization->institutionRelation->getInstitution();
            $institutions[$raInstitutionName] = new Institution($raInstitutionName);
        }

        foreach ($institutions as $institution) {
            if ($this->raListingRepository->findByIdentityIdAndInstitution($identityId, $institution)) {
                continue;
            }

            // create candidate if not exists
            $candidate = $this->raCandidateRepository->findByIdentityIdAndRaInstitution($identityId, $institution);
            if (!$candidate) {
                $candidate = RaCandidate::nominate(
                    $identityId,
                    $identityInstitution,
                    $identityNameId,
                    $identityCommonName,
                    $identityEmail,
                    $institution
                );
            }

            $this->raCandidateRepository->merge($candidate);
        }
    }
}
