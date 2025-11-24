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

namespace Surfnet\Stepup\Tests\DateTime;

use DateInterval;
use DateTime as CoreDateTime;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\DateTime\DateTime;

class DateTimeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * Might seem a bit overdone, but we rely on this specific format in quite a bit of places. If the format changes
     * this might lead to some unforeseen errors. This ensures that if the format is changed, this test fails and
     * that you're hopefully reading this as an instruction to check all the places that handle datetime for
     * compatibility with the new format. Think about log(-processors), (de-)serializers, etc.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function the_configured_format_is_what_is_needed_for_correct_application_behavior(): void
    {
        $this->assertEquals('Y-m-d\\TH:i:sP', DateTime::FORMAT);
    }

    /**
     * Ensure that the __toString of our DateTime object actually uses the correct format. For the reason why, read the
     * docblock above the {@see the_configured_format_is_what_is_needed_for_correct_application_behavior()} test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function to_string_returns_the_time_in_the_correct_format(): void
    {
        $coreDateTimeObject = new CoreDateTime('@1000');
        $ourDateTimeObject = new DateTime(new CoreDateTime('@1000'));

        $this->assertEquals($coreDateTimeObject->format(DateTime::FORMAT), (string)$ourDateTimeObject);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function add_returns_a_different_object_that_has_the_interval_added(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $interval = new DateInterval('PT1S');

        $result = $base->add($interval);

        $this->assertFalse($result === $base, 'DateTime::add must return a different object');
        $this->assertTrue($result > $base, 'DateTime::add adds the interval to the new object');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function sub_returns_a_different_object_that_has_the_interval_substracted(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $interval = new DateInterval('PT1S');

        $result = $base->sub($interval);

        $this->assertFalse($result === $base, 'DateTime::sub must return a different object');
        $this->assertTrue($result < $base, 'DateTime::sub subtracts the interval to the new object');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function comes_before_works_with_exclusive_comparison(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $before = new DateTime(new CoreDateTime('@999'));
        $same = new DateTime(new CoreDateTime('@1000'));
        $after = new DateTime(new CoreDateTime('@1001'));

        $this->assertTrue($before->comesBefore($base));
        $this->assertFalse($same->comesBefore($base));
        $this->assertFalse($after->comesBefore($base));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function comes_before_or_is_equal_works_with_inclusive_comparison(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $before = new DateTime(new CoreDateTime('@999'));
        $same = new DateTime(new CoreDateTime('@1000'));
        $after = new DateTime(new CoreDateTime('@1001'));

        $this->assertTrue($before->comesBeforeOrIsEqual($base));
        $this->assertTrue($same->comesBeforeOrIsEqual($base));
        $this->assertFalse($after->comesBeforeOrIsEqual($base));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function comes_after_works_with_exclusive_comparison(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $before = new DateTime(new CoreDateTime('@999'));
        $same = new DateTime(new CoreDateTime('@1000'));
        $after = new DateTime(new CoreDateTime('@1001'));

        $this->assertFalse($before->comesAfter($base));
        $this->assertFalse($same->comesAfter($base));
        $this->assertTrue($after->comesAfter($base));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function comes_after_or_is_equal_works_with_inclusive_comparison(): void
    {
        $base = new DateTime(new CoreDateTime('@1000'));
        $before = new DateTime(new CoreDateTime('@999'));
        $same = new DateTime(new CoreDateTime('@1000'));
        $after = new DateTime(new CoreDateTime('@1001'));

        $this->assertFalse($before->comesAfterOrIsEqual($base));
        $this->assertTrue($same->comesAfterOrIsEqual($base));
        $this->assertTrue($after->comesAfterOrIsEqual($base));
    }
}
