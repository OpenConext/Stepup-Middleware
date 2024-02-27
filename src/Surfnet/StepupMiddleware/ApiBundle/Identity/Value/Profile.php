<?php

/**
 * Copyright 2019 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Value;

use JsonSerializable;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;

class Profile implements JsonSerializable
{
    private Identity $identity;

    private AuthorizedInstitutionCollection $authorizedInstitutionCollection;

    /**
     * @var bool
     */
    private $isSraa;

    /**
     * @param Identity $identity
     * @param AuthorizedInstitutionCollection $authorizedInstitutionCollection
     *
     * @param bool $isSraa
     */
    public function __construct(
        Identity $identity,
        AuthorizedInstitutionCollection $authorizedInstitutionCollection,
        $isSraa
    ) {
        $this->identity = $identity;
        $this->authorizedInstitutionCollection = $authorizedInstitutionCollection;
        $this->isSraa = $isSraa;
    }

    public function jsonSerialize()
    {
        $profile = $this->identity->jsonSerialize();
        $profile["is_sraa"] = $this->isSraa;
        $profile["authorizations"] = $this->authorizedInstitutionCollection->getAuthorizations();

        return $profile;
    }
}
