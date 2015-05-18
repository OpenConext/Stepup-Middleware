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

namespace Surfnet\Stepup\Identity\Value;

use Broadway\Serializer\SerializableInterface;
use JsonSerializable;

final class SecondFactorIdentifier implements JsonSerializable, SerializableInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    private function __construct($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @param GssfId $gssfId
     * @return SecondFactorIdentifier
     */
    public static function fromGssfId(GssfId $gssfId)
    {
        return new self($gssfId->getGssfId(), get_class($gssfId));
    }

    /**
     * @param PhoneNumber $phoneNumber
     * @return SecondFactorIdentifier
     */
    public static function fromPhoneNumber(PhoneNumber $phoneNumber)
    {
        return new self($phoneNumber->getPhoneNumber(), get_class($phoneNumber));
    }

    /**
     * @param YubikeyPublicId $yubikeyPublicId
     * @return SecondFactorIdentifier
     */
    public static function fromYubikeyPublicId(YubikeyPublicId $yubikeyPublicId)
    {
        return new self($yubikeyPublicId->getYubikeyPublicId(), get_class($yubikeyPublicId));
    }

    /**
     * @param SecondFactorIdentifier $secondFactorIdentifier
     * @return bool
     */
    public function equals(SecondFactorIdentifier $secondFactorIdentifier)
    {
        return $this->type === $secondFactorIdentifier->type && $this->value === $secondFactorIdentifier->value;
    }

    public static function deserialize(array $data)
    {
        return new self($data['value'], $data['type']);
    }

    public function serialize()
    {
        return ['value' => $this->value, 'type' => $this->type];
    }

    public function jsonSerialize()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
