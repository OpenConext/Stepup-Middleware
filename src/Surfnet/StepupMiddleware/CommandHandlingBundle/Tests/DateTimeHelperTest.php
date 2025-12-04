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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests;

use DateTime as CoreDateTime;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\DateTime\DateTime;

#[RunTestsInSeparateProcesses]
class DateTimeHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[Group('testing')]
    public function it_mocks_now(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $this->assertEquals(new DateTime(new CoreDateTime('@12345')), DateTime::now());
    }

    #[Test]
    #[Group('testing')]
    public function it_can_be_disabled_in_the_same_process(): void
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));
        $this->assertEquals(new DateTime(new CoreDateTime('@12345')), DateTime::now());

        DateTimeHelper::setCurrentTime(null);
        // Deliberately assigned temporary variable due to microsecond precision in PHP 7.1
        $now = DateTime::now();
        $this->assertTrue((new DateTime())->comesAfterOrIsEqual($now));
    }

    #[Test]
    #[Group('testing')]
    public function it_works_with_separate_processes(): void
    {
        // The stub value has been removed.
        // Deliberately assigned temporary variable due to microsecond precision in PHP 7.1
        $now = DateTime::now();
        $this->assertTrue((new DateTime())->comesAfterOrIsEqual($now));
    }
}
