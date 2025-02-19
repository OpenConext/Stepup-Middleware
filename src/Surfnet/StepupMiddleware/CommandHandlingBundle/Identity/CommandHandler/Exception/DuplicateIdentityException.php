<?php
/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\Exception;

use RuntimeException;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Throwable;

final class DuplicateIdentityException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     *
     * @return DuplicateIdentityException
     */
    public static function forBootstrappingWithYubikeySecondFactor(NameId $nameId, Institution $institution): self
    {
        return new self(
            sprintf(
                'Trying to bootstrap a duplicate identity: an identity with name ID "%s" from institution "%s" already exists.',
                $nameId->getNameId(),
                $institution->getInstitution(),
            ),
        );
    }
}
