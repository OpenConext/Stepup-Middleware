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
    public ?string $name = null;

    public ?string $type = null;

    /*
     * The second factor type's ID (eg. Yubikey public ID)
     */
    public ?string $secondFactorId = null;

    public ?string $email = null;

    /*
     * the filter value, not to be confused with the actorInstitution which is used for authorizations.
     */
    public ?string $institution = null;

    /*
     * One of the ApiBundle\Identity\Entity\RaSecondFactor::STATUS_* constants.
     */
    public ?string $status = null;

    public ?string $orderBy = null;

    public ?string $orderDirection = null;

    public InstitutionAuthorizationContextInterface $authorizationContext;
}
