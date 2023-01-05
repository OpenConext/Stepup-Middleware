<?php
/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\Stepup\Helper;

use Surfnet\Stepup\Identity\Value\HashedSecret;
use Surfnet\Stepup\Identity\Value\UnhashedSecret;

/**
 * Converts the unhashed secret to a hashed version
 *
 * Mainly created in order to allow mocking of this feature for
 * test purposes.
 */
class RecoveryTokenSecretHelper
{
    public function hash(UnhashedSecret $unhashedSecret): HashedSecret
    {
        return $unhashedSecret->hashSecret();
    }
}
