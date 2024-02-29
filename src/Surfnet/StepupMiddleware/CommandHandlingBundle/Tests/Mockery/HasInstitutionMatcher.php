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
use Mockery\Matcher\MatcherInterface;

final class HasInstitutionMatcher implements MatcherInterface
{
    public function __construct(private $expected)
    {
        if (!is_string($this->expected)) {
            throw new RuntimeException(
                sprintf('In order to use the %s, a string should be given.', self::class),
            );
        }
    }

    public function match(&$actual): bool
    {
        if (!is_object($actual)) {
            return false;
        }

        if (method_exists($actual, 'getInstitution')) {
            return $this->expected === $actual->getInstitution();
        }
        if (property_exists($actual, 'institution')) {
            return $this->expected === $actual->institution;
        }

        return false;
    }

    public function __toString(): string
    {
        return sprintf('<HasInstitutionMatcher($s)>', $this->expected);
    }
}
