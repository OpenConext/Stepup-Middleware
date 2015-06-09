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
    public function sensitiveDataApplications()
    {
        return [
            '0 sensitive data, 0 events' => [
                [],
                [],
                [],
            ],
            '1 sensitive data, 1 event' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 0,
                        'data' => (new SensitiveData())
                            ->withCommonName(new CommonName('Willie Willoughby'))
                    ],
                ],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 0,
                        'forgettable' => true,
                    ],
                ],
                [[0, 0]],
            ],
            '2 sensitive data, 2 events' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 0,
                        'data' => (new SensitiveData())->withCommonName(new CommonName('Willie Willoughby'))
                    ],
                    [
                        'id' => md5('1'),
                        'playhead' => 1,
                        'data' => (new SensitiveData())
                    ],
                ],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 0,
                        'forgettable' => true,
                    ],
                    [
                        'id'          => md5('1'),
                        'playhead'    => 1,
                        'forgettable' => true,
                    ],
                ],
                [[0, 0]],
            ],
            '1 forgotten sensitive data, 1 forgettable event, 1 regular event' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 1,
                        'data' => (new SensitiveData())->withEmail(new Email('null@domain.invalid'))->forget()
                    ],
                ],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 0,
                        'forgettable' => false,
                    ],
                    [
                        'id'          => md5('1'),
                        'playhead'    => 1,
                        'forgettable' => true,
                    ],
                ],
                [[0, 1]],
            ],
            '0 sensitive data, 1 forgettable event' => [
                [],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 1,
                        'forgettable' => true,
                    ],
                ],
                [],
                '/^Sensitive data is missing for event with UUID .+, playhead 1$/',
            ],
            '1 mismatched sensitive data, 1 regular event' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 0,
                        'data' => (new SensitiveData())->withEmail(new Email('null@domain.invalid'))
                    ],
                ],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 1,
                        'forgettable' => false,
                    ],
                ],
                [],
                '/^1 sensitive data messages are still to be matched to events$/',
            ],
            '1 sensitive data, 2 regular event of which 1 matches the sensitive data' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 1,
                        'data' => (new SensitiveData())->withEmail(new Email('null@domain.invalid'))
                    ],
                ],
                [
                    [
                        'id'          => md5('1'),
                        'playhead'    => 0,
                        'forgettable' => false,
                    ],
                    [
                        'id'          => md5('1'),
                        'playhead'    => 1,
                        'forgettable' => false,
                    ],
                ],
                [],
                '/^Encountered sensitive data for event which does not support sensitive data, UUID .+, playhead 1$/',
            ],
            '1 sensitive data, 1 forgettable event, stream ID mismatch' => [
                [
                    [
                        'id' => md5('1'),
                        'playhead' => 1,
                        'data' => (new SensitiveData())->withEmail(new Email('null@domain.invalid'))
                    ],
                ],
                [
                    [
                        'id'          => md5('2'),
                        'playhead'    => 1,
                        'forgettable' => true,
                    ],
                ],
                [],
                '/^Encountered sensitive data from stream .+ for event from stream .+$/',
            ],
        ];
    }

    /**
     * @test
     * @group sensitive-data
     * @dataProvider sensitiveDataApplications
     *
     * @param array       $sensitiveDataDescriptors
     * @param array       $eventDescriptors
     * @param array       $equalityAssertions
     * @param string|null $expectedErrorMessage
     */
    public function it_sets_sensitive_data_on_events($sensitiveDataDescriptors, $eventDescriptors, $equalityAssertions, $expectedErrorMessage = null)
    {
        if ($expectedErrorMessage) {
            $this->setExpectedExceptionRegExp(
                'Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Exception\SensitiveDataApplicationException',
                $expectedErrorMessage
            );
        }

        $sensitiveDataMessages = $this->createSensitiveDataMessages($sensitiveDataDescriptors);
        $domainMessages = $this->createDomainMessages($eventDescriptors);
        $this->apply($sensitiveDataMessages, $domainMessages);

        foreach ($equalityAssertions as $equalityAssertion) {
            $this->assertSensitiveDataEquals(
                $sensitiveDataMessages[$equalityAssertion[0]],
                $domainMessages[$equalityAssertion[1]]
            );
        }
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

    private function createSensitiveDataMessages(array $descriptors)
    {
        return array_map([$this, 'createSensitiveDataMessage'], $descriptors);
    }

    private function createSensitiveDataMessage(array $descriptor)
    {
        return new SensitiveDataMessage(new IdentityId($descriptor['id']), $descriptor['playhead'], $descriptor['data']);
    }

    private function createDomainMessages(array $descriptors)
    {
        return array_map([$this, 'createDomainMessage'], $descriptors);
    }

    private function createDomainMessage(array $descriptor)
    {
        return new DomainMessage(
            $descriptor['id'],
            $descriptor['playhead'],
            new Metadata(),
            $descriptor['forgettable'] ? new ForgettableEvent() : new RegularEvent(),
            DateTime::now()
        );
    }

    private function pluck($key, &$array)
    {
        $value = $array[$key];
        unset($array[$key]);

        return $value;
    }
}
