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
use Surfnet\Stepup\Configuration\Event\InstitutionConfigurationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRemovedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\Event\ShowRaaContactInformationOptionChangedEvent;
use Surfnet\Stepup\Configuration\Event\UseRaLocationsOptionChangedEvent;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationList;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
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
     * @param InstitutionConfigurationId $institutionConfigurationId
     * @param Institution $institution
     * @return InstitutionConfiguration
     */
    public static function create(InstitutionConfigurationId $institutionConfigurationId, Institution $institution)
    {
        $useRaLocationsOption            = new UseRaLocationsOption(false);
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);

        $institutionConfiguration = new self;
        $institutionConfiguration->apply(
            new NewInstitutionConfigurationCreatedEvent(
                $institutionConfigurationId,
                $institution,
                $useRaLocationsOption,
                $showRaaContactInformationOption
            )
        );

        return $institutionConfiguration;
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
        $this->raLocations                     = new RaLocationList([]);
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
        // reset to defaults. This way, should it be resurrected, it seems it is new again
        $this->raLocations                     = [];
        $this->useRaLocationsOption            = new UseRaLocationsOption(false);
        $this->showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
    }
}
