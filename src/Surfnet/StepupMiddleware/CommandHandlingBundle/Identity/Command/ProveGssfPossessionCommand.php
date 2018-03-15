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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Symfony\Component\Validator\Constraints as Assert;

class ProveGssfPossessionCommand extends AbstractCommand implements SelfServiceExecutable
{
    /**
     * The ID of an existing identity.
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $identityId;

    /**
     * The ID of the second factor to create.
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $secondFactorId;

    /**
     * The SecondFactorType identifier. 
     * 
     * For example in the case of a Tiqr GSSP it would be 'tiqr'.
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $stepupProvider;

    /**
     * The identifier of the generic Stepup second factor type
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     *
     * @var string
     */
    public $gssfId;
}
