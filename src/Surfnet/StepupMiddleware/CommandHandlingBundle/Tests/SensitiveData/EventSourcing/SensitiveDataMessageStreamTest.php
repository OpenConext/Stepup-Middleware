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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\SensitiveData\EventSourcing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessage;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\EventSourcing\SensitiveDataMessageStream;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

final class SensitiveDataMessageStreamTest extends TestCase
{
    const EVENT_STREAM_A = 'A';
    const EVENT_STREAM_B = 'B';

    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_work_with_zero_sensitive_data_messages_and_zero_events()
    {
        $this->apply([], []);
    }

    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_apply_one_sensitive_data_message_to_one_matching_event()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                0,
                (new SensitiveData)->withCommonName(new CommonName('Willie Willoughby'))
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
        $this->assertSensitiveDataEquals($sensitiveDataMessages[0], $domainMessages[0]);
    }

    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_apply_two_sensitive_data_message_to_two_matching_events()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                0,
                (new SensitiveData)->withCommonName(new CommonName('Willie Willoughby'))
            ),
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                1,
                (new SensitiveData)->withEmail(new Email('willie@willougby.invalid'))
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
            new DomainMessage(
                self::EVENT_STREAM_A,
                1,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
        $this->assertSensitiveDataEquals($sensitiveDataMessages[0], $domainMessages[0]);
        $this->assertSensitiveDataEquals($sensitiveDataMessages[1], $domainMessages[1]);
    }

    /**
     * @test
     * @group sensitive-data
     */
    public function it_can_apply_one_sensitive_data_message_to_one_regular_event_and_one_matching_forgettable_event()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                1,
                (new SensitiveData)->withEmail(new Email('willie@willougby.invalid'))->forget()
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new RegularEventStub(),
                DateTime::now()
            ),
            new DomainMessage(
                self::EVENT_STREAM_A,
                1,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
        $this->assertSensitiveDataEquals($sensitiveDataMessages[0], $domainMessages[1]);
    }

    /**
     * @test
     * @group sensitive-data
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException
     * @expectedExceptionMessage Sensitive data is missing for event with UUID A, playhead 0
     */
    public function it_fails_when_sensitive_data_is_missing_for_an_event()
    {
        $sensitiveDataMessages = [];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
    }

    /**
     * @test
     * @group sensitive-data
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException
     * @expectedExceptionMessage 1 sensitive data messages are still to be matched to events
     */
    public function it_fails_when_not_all_sensitive_data_could_be_matched_to_an_event()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                1,
                (new SensitiveData)->withEmail(new Email('willie@willougby.invalid'))->forget()
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new RegularEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
    }

    /**
     * @test
     * @group sensitive-data
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException
     * @expectedExceptionMessage Encountered sensitive data for event which does not support sensitive data, UUID A, playhead 0
     */
    public function it_fails_when_sensitive_data_matches_a_regular_event()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                0,
                (new SensitiveData)->withEmail(new Email('willie@willougby.invalid'))->forget()
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_A,
                0,
                new Metadata(),
                new RegularEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
    }

    /**
     * @test
     * @group sensitive-data
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException
     * @expectedExceptionMessage Encountered sensitive data from stream A for event from stream B
     */
    public function it_fails_when_stream_ids_dont_match()
    {
        $sensitiveDataMessages = [
            new SensitiveDataMessage(
                new IdentityId(self::EVENT_STREAM_A),
                0,
                (new SensitiveData)->withEmail(new Email('willie@willougby.invalid'))->forget()
            ),
        ];
        $domainMessages = [
            new DomainMessage(
                self::EVENT_STREAM_B,
                0,
                new Metadata(),
                new ForgettableEventStub(),
                DateTime::now()
            ),
        ];

        $this->apply($sensitiveDataMessages, $domainMessages);
    }

    private function apply(array $sensitiveDataMessages, array $domainMessages)
    {
        (new SensitiveDataMessageStream($sensitiveDataMessages))
            ->applyToDomainEventStream(new DomainEventStream($domainMessages));
    }

    private function assertSensitiveDataEquals(SensitiveDataMessage $sensitiveDataMessage, DomainMessage $domainMessage)
    {
        $this->assertEquals($sensitiveDataMessage->getSensitiveData(), $domainMessage->getPayload()->sensitiveData);
    }
}
