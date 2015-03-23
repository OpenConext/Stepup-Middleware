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

abstract class AbstractCommand implements Command
{
    /**
     * @Assert\NotBlank(message="stepup.command.command_uuid.must_not_be_blank")
     * @Assert\Type(type="string", message="stepup.command.command_uuid.must_be_string")
     *
     * @var string
     */
    public $UUID;

    public function __toString()
    {
        return get_class($this) . '[' . $this->UUID . ']';
    }
}
