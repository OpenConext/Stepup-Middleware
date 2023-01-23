<?php

/**
 * Copyright 2021 SURFnet B.V.
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

use JsonSerializable;

interface VettingType extends JsonSerializable
{
    public const TYPE_ON_PREMISE = 'on-premise';
    public const TYPE_SELF_VET = 'self-vet';

    /**
     * A vetting type is categorized by the way the identity of the user was established. At
     * this point there are two categories
     */

    /**
     * The Identity was proven in a self-asserted manner. By proving possession of a
     * fysical token without intervention of a third party. The identity asserts
     * his/her identity his/herself.
     */
    public const CATEGORY_IDENTITY_SELF_ASSERTED = 'self-asserted-identity';

    /**
     * The identity is proven by visiting a fysical vetting station. This means the
     * identity visits a RA(A) that verifies the identity of the user by checking
     * a legal document (passport/drivers licence).
     */
    public const CATEGORY_IDENTITY_VETTED = 'identity-vetted';

    public function auditLog(): string;

    public function type(): string;

    public function __toString(): string;

    public function getDocumentNumber(): ?DocumentNumber;
}
