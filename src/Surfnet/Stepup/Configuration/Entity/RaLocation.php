<?php

namespace Surfnet\Stepup\Configuration\Entity;

use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocation
{
    /**
     * @var RaLocationId
     */
    private $raLocationId;

    /**
     * @var RaLocationName
     */
    private $locationName;

    /**
     * @var Location
     */
    private $location;

    /**
     * @var ContactInformation
     */
    private $contactInformation;

    /**
     * @param RaLocationId $raLocationId
     * @param RaLocationName $raLocationName
     * @param Location $location
     * @param ContactInformation $contactInformation
     * @return RaLocation
     */
    public static function create(
        RaLocationId $raLocationId,
        RaLocationName $raLocationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        return new self($raLocationId, $raLocationName, $location, $contactInformation);
    }

    private function __construct(
        RaLocationId $raLocationId,
        RaLocationName $locationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $this->raLocationId       = $raLocationId;
        $this->locationName       = $locationName;
        $this->location           = $location;
        $this->contactInformation = $contactInformation;
    }

    public function hasRaLocationId(RaLocationId $raLocationId)
    {
        return $this->raLocationId->equals($raLocationId);
    }

    /**
     * @return RaLocationId
     */
    public function getRaLocationId()
    {
        return $this->raLocationId;
    }

    /**
     * @return RaLocationName
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return ContactInformation
     */
    public function getContactInformation()
    {
        return $this->contactInformation;
    }
}
