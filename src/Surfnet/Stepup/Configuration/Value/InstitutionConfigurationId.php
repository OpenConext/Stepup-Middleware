<?php

namespace Surfnet\Stepup\Configuration\Value;

use Rhumsaa\Uuid\Uuid;

final class InstitutionConfigurationId
{
    const UUID_NAMESPACE = '09876543-abcd-0987-abcd-098765432109';

    private $uuid;

    /**
     * @param Institution $institution
     * @return InstitutionConfigurationId
     */
    public static function from(Institution $institution)
    {
        return new self(Uuid::uuid5(self::UUID_NAMESPACE, $institution->getInstitution()));
    }

    private function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @param InstitutionConfigurationId $otherInstitutionConfigurationId
     * @return bool
     */
    public function equals(InstitutionConfigurationId $otherInstitutionConfigurationId)
    {
        return $this->uuid === $otherInstitutionConfigurationId->uuid;
    }
}
