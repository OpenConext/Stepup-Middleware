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

use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;

final class RaSecondFactorQuery extends AbstractQuery
{
    /**
     * @var string|\Surfnet\Stepup\Identity\Value\Institution
     */
    public $actorInstitution;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $type;

    /**
     * @var string|null The second factor type's ID (eg. Yubikey public ID)
     */
    public $secondFactorId;

    /**
     * @var string|null
     */
    public $email;

    /**
     * @var string|null the filter value, not to be confused with the actorInstitution which is used for authorizations.
     */
    public $institution;

    /**
     * @var string|null One of the ApiBundle\Identity\Entity\RaSecondFactor::STATUS_* constants.
     */
    public $status;

    /**
     * @var string|null
     */
    public $orderBy;

    /**
     * @var string|null
     */
    public $orderDirection;

    /**
     * @var InstitutionAuthorizationContextInterface
     */
    public $authorizationContext;
}
