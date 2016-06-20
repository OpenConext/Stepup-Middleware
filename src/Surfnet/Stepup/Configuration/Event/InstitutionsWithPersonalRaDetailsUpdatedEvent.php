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

namespace Surfnet\Stepup\Configuration\Event;

final class InstitutionsWithPersonalRaDetailsUpdatedEvent extends ConfigurationEvent
{
    /**
     * @var string[]
     */
    public $institutionsWithPersonalRaDetails;

    /**
     * @param string $configurationId
     * @param string[] $institutionsWithPersonalRaDetails
     */
    public function __construct($configurationId, array $institutionsWithPersonalRaDetails)
    {
        parent::__construct($configurationId);

        $this->institutionsWithPersonalRaDetails = $institutionsWithPersonalRaDetails;
    }

    public static function deserialize(array $data)
    {
        return new self($data['id'], $data['institutions_with_personal_ra_details']);
    }

    public function serialize()
    {
        return [
            'id'                                    => $this->id,
            'institutions_with_personal_ra_details' => $this->institutionsWithPersonalRaDetails,
        ];
    }
}
