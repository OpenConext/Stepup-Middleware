<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Dto;

class VettingLocation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $location;

    /**
     * @var string
     */
    public $contactInformation;


    public function __construct($name, $location, $contactInformation)
    {
        $this->name               = $name;
        $this->location           = $location;
        $this->contactInformation = $contactInformation;
    }
}
