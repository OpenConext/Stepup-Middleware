<?php

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocation
{
    /**
     * @var RaLocationName
     */
    private $locationName;

    /**
     * @var RaLocationId
     */
    private $raLocationId;

    /**
     * @var ContactInformation
     */
    private $contactInformation;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var Location
     */
    private $location;

    public function __construct(
        RaLocationId $raLocationId,
        Institution $institution,
        RaLocationName $locationName,
        Location $location,
        ContactInformation $contactInformation
    ) {
        $this->raLocationId       = $raLocationId;
        $this->institution        = $institution;
        $this->locationName       = $locationName;
        $this->location           = $location;
        $this->contactInformation = $contactInformation;
    }
}
