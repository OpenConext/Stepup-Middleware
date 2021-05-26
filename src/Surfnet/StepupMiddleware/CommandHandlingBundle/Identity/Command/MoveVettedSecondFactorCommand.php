<?php

/**
 * Copyright 2021 SURF bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

class MoveVettedSecondFactorCommand extends AbstractCommand
{

    /**
     * The UUID of the source identity
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $sourceIdentityId;

    /**
     * The UUID of the target identity
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $targetIdentityId;

    /**
     * The source second factor UUID that is to be moved
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $sourceSecondFactorId;


    /**
     * The new destination second factor UUID
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $targetSecondFactorId;
}
