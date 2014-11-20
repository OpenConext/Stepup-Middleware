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

namespace Surfnet\Stepup\Identity\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class EmailVerifiedEvent implements SerializableInterface
{
    /**
     * @var SecondFactorId
     */
    public $secondFactorId;

    /**
     * @param SecondFactorId $secondFactorId
     */
    public function __construct(SecondFactorId $secondFactorId)
    {
        $this->secondFactorId = $secondFactorId;
    }

    public static function deserialize(array $data)
    {
        return new self(new SecondFactorId($data['second_factor_id']));
    }

    public function serialize()
    {
        return ['second_factor_id' => (string) $this->secondFactorId];
    }
}
