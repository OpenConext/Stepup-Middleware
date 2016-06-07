<?php

namespace Surfnet\Stepup\Configuration\Value;

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class RaLocationName
{
    /**
     * @var string
     */
    private $raLocationName;

    /**
     * @param string $raLocationName
     */
    public function __construct($raLocationName)
    {
        if (!is_string($raLocationName) || trim($raLocationName) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'raLocationName', $raLocationName);
        }

        $this->raLocationName = $raLocationName;
    }

    /**
     * @param RaLocationName $otherRaLocationName
     * @return bool
     */
    public function equals(RaLocationName $otherRaLocationName)
    {
        return $this->raLocationName === $otherRaLocationName->raLocationName;
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }

    public function __toString()
    {
        return $this->raLocationName;
    }
}
