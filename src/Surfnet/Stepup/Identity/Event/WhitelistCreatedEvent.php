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

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;

class WhitelistCreatedEvent implements WhitelistEvent
{
    /**
     * @var InstitutionCollection
     */
    public InstitutionCollection $whitelistedInstitutions;

    public function __construct(InstitutionCollection $institutionCollection)
    {
        $this->whitelistedInstitutions = $institutionCollection;
    }

    /**
     * @param array $data
     * @return WhitelistCreatedEvent
     */
    public static function deserialize(array $data): self
    {
        return new self(InstitutionCollection::deserialize($data['whitelisted_institutions']));
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return ['whitelisted_institutions' => $this->whitelistedInstitutions->serialize()];
    }
}
