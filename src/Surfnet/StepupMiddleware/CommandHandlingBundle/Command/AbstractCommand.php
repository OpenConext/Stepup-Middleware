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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren) we simply have a lot of commands
 * @SuppressWarnings(PHPMD.CamelCasePropertyName) UUID was not camel cased intentionally
 */
abstract class AbstractCommand implements Command
{
    /**
     *
     * @var string
     */
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[Assert\Regex(pattern: '~^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$~i')]
    public $UUID;

    public function __toString(): string
    {
        return get_class($this) . '[' . $this->UUID . ']';
    }
}
