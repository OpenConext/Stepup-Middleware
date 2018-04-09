<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Configuration\Api\InstitutionConfiguration as InstitutionConfigurationInterface;
use Surfnet\Stepup\Configuration\Entity\RaLocation;
use Surfnet\Stepup\Configuration\Event\AllowedSecondFactorListUpdatedEvent;
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\NumberOfTokensPerIdentityOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\VerifyEmailOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationList;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Configuration\Value\VerifyEmailOption;
use Surfnet\Stepup\Exception\DomainException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Events and value objects
 * @SuppressWarnings(PHPMD.TooManyPublicMethods) AggregateRoot
 */
class InstitutionConfiguration extends EventSourcedAggregateRoot implements InstitutionConfigurationInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    private $institutionConfigurationId;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var RaLocationList
     */
    private $raLocations;

    /**
     * @var UseRaLocationsOption
     */
    private $useRaLocationsOption;

    /**
     * @var ShowRaaContactInformationOption
     */
    private $showRaaContactInformationOption;

    /**
     * @var VerifyEmailOption
     */
    private $verifyEmailOption;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    private $numberOfTokensPerIdentityOption;

    /**
     * @var AllowedSecondFactorList
     */
    private $allowedSecondFactorList;

    /**
     * @var boolean
     */
    private $isMarkedAsDestroyed;

    /**
     * @param InstitutionConfigurationId $institutionConfigurationId
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    public static function create(InstitutionConfigurationId $institutionConfigurationId, Institution $institution)
    {
        $institutionConfiguration = new self;
        $institutionConfiguration->apply(
            new NewInstitutionConfigurationCreatedEvent(
                $institutionConfigurationId,
                $institution,
                UseRaLocationsOption::getDefault(),
                ShowRaaContactInformationOption::getDefault(),
                VerifyEmailOption::getDefault(),
                NumberOfTokensPerIdentityOption::getDefault()
            )
        );
        $institutionConfiguration->apply(new AllowedSecondFactorListUpdatedEvent(
            $institutionConfigurationId,
            $institution,
            AllowedSecondFactorList::blank()
        ));

        return $institutionConfiguration;
    }

    /**
     * @return InstitutionConfiguration
     */
    public function rebuild()
    {
        // We can only rebuild a destroyed InstitutionConfiguration, all other cases are not valid
        if ($this->isMarkedAsDestroyed !== true) {
            throw new DomainException('Cannot rebuild InstitutionConfiguration as it has not been destroyed');
        }

        $this->apply(
            new NewInstitutionConfigurationCreatedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                UseRaLocationsOption::getDefault(),
                ShowRaaContactInformationOption::getDefault(),
                VerifyEmailOption::getDefault(),
                NumberOfTokensPerIdentityOption::getDefault()
            )
        );
        $this->apply(new AllowedSecondFactorListUpdatedEvent(
            $this->institutionConfigurationId,
            $this->institution,
            AllowedSecondFactorList::blank()
        ));

        return $this;
    }

    final public function __construct()
    {
    }

    public function configureUseRaLocationsOption(UseRaLocationsOption $useRaLocationsOption)
    {
        if ($this->useRaLocationsOption->equals($useRaLocationsOption)) {
            return;
        }

        $this->apply(
            new UseRaLocationsOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $useRaLocationsOption
            )
        );
    }

    public function configureShowRaaContactInformationOption(ShowRaaContactInformationOption $showRaaContactInformationOption)
    {
        if ($this->showRaaContactInformationOption->equals($showRaaContactInformationOption)) {
            return;
        }

        $this->apply(
            new ShowRaaContactInformationOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $showRaaContactInformationOption
            )
        );
    }

    public function configureVerifyEmailOption(VerifyEmailOption $verifyEmailOption)
    {
        if ($this->verifyEmailOption->equals($verifyEmailOption)) {
            return;
        }

        $this->apply(
            new VerifyEmailOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $verifyEmailOption
            )
        );
    }

    public function configureNumberOfTokensPerIdentityOption(
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption
    ) {
        if ($this->numberOfTokensPerIdentityOption->equals($numberOfTokensPerIdentityOption)) {
            return;
        }

        $this->apply(
            new NumberOfTokensPerIdentityOptionChangedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $numberOfTokensPerIdentityOption
            )
        );
    }

    public function updateAllowedSecondFactorList(AllowedSecondFactorList $allowedSecondFactorList)
    {
        // AllowedSecondFactorList can be null for InstitutionConfigurations for which this functionality did not exist
        if ($this->allowedSecondFactorList !== null
            && $this->allowedSecondFactorList->equals($allowedSecondFactorList)
        ) {
            return;
        }

        $this->apply(
            new AllowedSecondFactorListUpdatedEvent(
                $this->institutionConfigurationId,
                $this->institution,
                $allowedSecondFactorList
            )
        );
    }

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     */
    public function addRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if ($this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot add RaLocation with RaLocationId "%s" to RaLocations of InstitutionConfiguration "%s":'
                . ' it is already present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        $this->apply(new RaLocationAddedEvent(
            $this->institutionConfigurationId,
            $this->institution,
            $raLocationId,
            $raLocationName,
            $location,
            $contactInformation
        ));
    }

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     */
    public function changeRaLocation(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        if (!$this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot change RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                . ' it is not present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        $raLocation = $this->raLocations->getById($raLocationId);

        if (!$raLocation->getName()->equals($raLocationName)) {
            $this->apply(
                new RaLocationRenamedEvent($this->institutionConfigurationId, $raLocationId, $raLocationName)
            );
        }
        if (!$raLocation->getLocation()->equals($location)) {
            $this->apply(
                new RaLocationRelocatedEvent($this->institutionConfigurationId, $raLocationId, $location)
            );
        }
        if (!$raLocation->getContactInformation()->equals($contactInformation)) {
            $this->apply(
                new RaLocationContactInformationChangedEvent(
                    $this->institutionConfigurationId,
                    $raLocationId,
                    $contactInformation
                )
            );
        }
    }

    /**
     * @param RaLocationId $raLocationId
     */
    public function removeRaLocation(RaLocationId $raLocationId)
    {
        if (!$this->raLocations->containsWithId($raLocationId)) {
            throw new DomainException(sprintf(
                'Cannot remove RaLocation with RaLocationId "%s" in RaLocations of InstitutionConfiguration "%s":'
                . ' it is not present',
                $raLocationId,
                $this->getAggregateRootId()
            ));
        }

        $this->apply(new RaLocationRemovedEvent($this->institutionConfigurationId, $raLocationId));
    }

    /**
     * @return void
     */
    public function destroy()
    {
        $this->apply(new InstitutionConfigurationRemovedEvent($this->institutionConfigurationId, $this->institution));
    }

    public function getAggregateRootId()
    {
        return $this->institutionConfigurationId;
    }

    protected function applyNewInstitutionConfigurationCreatedEvent(NewInstitutionConfigurationCreatedEvent $event)
    {
        $this->institutionConfigurationId      = $event->institutionConfigurationId;
        $this->institution                     = $event->institution;
        $this->useRaLocationsOption            = $event->useRaLocationsOption;
        $this->showRaaContactInformationOption = $event->showRaaContactInformationOption;
        $this->verifyEmailOption               = $event->verifyEmailOption;
        $this->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;
        $this->raLocations                     = new RaLocationList([]);
        $this->isMarkedAsDestroyed             = false;
    }

    protected function applyUseRaLocationsOptionChangedEvent(UseRaLocationsOptionChangedEvent $event)
    {
        $this->useRaLocationsOption = $event->useRaLocationsOption;
    }

    protected function applyShowRaaContactInformationOptionChangedEvent(
        ShowRaaContactInformationOptionChangedEvent $event
    ) {
        $this->showRaaContactInformationOption = $event->showRaaContactInformationOption;
    }

    protected function applyVerifyEmailOptionChangedEvent(
        VerifyEmailOptionChangedEvent $event
    ) {
        $this->verifyEmailOption = $event->verifyEmailOption;
    }

    protected function applyNumberOfTokensPerIdentityOptionChangedEvent(
        NumberOfTokensPerIdentityOptionChangedEvent $event
    ) {
        $this->numberOfTokensPerIdentityOption = $event->numberOfTokensPerIdentityOption;
    }

    protected function applyAllowedSecondFactorListUpdatedEvent(AllowedSecondFactorListUpdatedEvent $event)
    {
        $this->allowedSecondFactorList = $event->allowedSecondFactorList;
    }

    protected function applyRaLocationAddedEvent(RaLocationAddedEvent $event)
    {
        $this->raLocations->add(
            RaLocation::create(
                $event->raLocationId,
                $event->raLocationName,
                $event->location,
                $event->contactInformation
            )
        );
    }

    protected function applyRaLocationRenamedEvent(RaLocationRenamedEvent $event)
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->rename($event->raLocationName);
    }

    protected function applyRaLocationRelocatedEvent(RaLocationRelocatedEvent $event)
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->relocate($event->location);
    }

    protected function applyRaLocationContactInformationChangedEvent(RaLocationContactInformationChangedEvent $event)
    {
        $raLocation = $this->raLocations->getById($event->raLocationId);
        $raLocation->changeContactInformation($event->contactInformation);
    }

    protected function applyRaLocationRemovedEvent(RaLocationRemovedEvent $event)
    {
        $this->raLocations->removeWithId($event->raLocationId);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param InstitutionConfigurationRemovedEvent $event
     */
    protected function applyInstitutionConfigurationRemovedEvent(InstitutionConfigurationRemovedEvent $event)
    {
        // reset all configuration to defaults. This way, should it be rebuild, it seems like it is new again
        $this->raLocations                     = new RaLocationList([]);
        $this->useRaLocationsOption            = UseRaLocationsOption::getDefault();
        $this->showRaaContactInformationOption = ShowRaaContactInformationOption::getDefault();
        $this->verifyEmailOption               = VerifyEmailOption::getDefault();
        $this->numberOfTokensPerIdentityOption = NumberOfTokensPerIdentityOption::getDefault();
        $this->allowedSecondFactorList         = AllowedSecondFactorList::blank();

        $this->isMarkedAsDestroyed             = true;
    }
}
