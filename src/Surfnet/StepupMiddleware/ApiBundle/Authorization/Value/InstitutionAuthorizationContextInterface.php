<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Value;

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;

/**
 * Interface to implement InstitutionAuthorizationContext
 *
 * This context can be found on some of the queries and indicates who initiated the
 * query. This information will be used to verify if this user is authorized to yield
 * results for its own institution or also from other institutions he/she is (S)RA(A)
 * for.
 *
 * The role requirements describe which role requirements need to be configured for the
 * institution of the actor, in order to determine what data should be retrieved.
 */
interface InstitutionAuthorizationContextInterface
{
    /**
     * @return InstitutionCollection
     */
    public function getInstitutions();

    /**
     * @return bool
     */
    public function isActorSraa();
}
