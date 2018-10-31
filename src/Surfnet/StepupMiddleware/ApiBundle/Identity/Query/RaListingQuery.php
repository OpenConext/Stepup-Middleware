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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Query;

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;

class RaListingQuery extends AbstractQuery
{
    /**
     * @var string|\Surfnet\Stepup\Identity\Value\Institution
     */
    public $actorInstitution;

    /**
     * @var string|\Surfnet\Stepup\Identity\Value\Institution
     */
    public $institution;

    /**
     * @var IdentityId
     */
    public $identityId;

    /**
     * @var string
     */
    public $orderBy;

    /**
     * @var string
     */
    public $orderDirection;

    /**
     * {@inheritdoc} RaListing should not be paginated, expectation is that amount of entries remains well under 100,
     * if there are issue they will be tackled later as requested by SURFnet.
     */
    public $itemsPerPage = 1000;

    /**
     * @var InstitutionAuthorizationContextInterface
     */
    public $authorizationContext;
}
