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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Mockery;

use Mockery\Exception\RuntimeException;
use Mockery\Matcher\MatcherAbstract;
use Surfnet\Stepup\Configuration\Value\Institution;

final class HasConfigurationInstitutionMatcher extends MatcherAbstract
{
    public function __construct($expected)
    {
        if (!$expected instanceof Institution) {
            throw new RuntimeException(
                sprintf('In order to use the %s, a "%s" object should be given.', self::class, Institution::class)
            );
        }

        parent::__construct($expected);
    }

    public function match(&$actual)
    {
        if (!is_object($actual) || !property_exists($actual, 'institution')) {
            return false;
        }

        if (!$actual->institution instanceof Institution) {
            return false;
        }

        return $this->_expected->equals($actual->institution);
    }

    public function __toString()
    {
        return sprintf('<HasConfigurationInstitutionMatcher($s)>', $this->_expected);
    }
}
