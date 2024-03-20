<?php

/**
 * Copyright 2022 SURFnet bv
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

class RecoveryTokenQuery extends AbstractQuery
{
    public ?IdentityId $identityId = null;

    public ?string $type = null;

    public ?string $status = null;

    public ?string $institution = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $orderBy = null;

    public ?string $orderDirection = null;

    public ?InstitutionAuthorizationContextInterface $authorizationContext = null;
}
