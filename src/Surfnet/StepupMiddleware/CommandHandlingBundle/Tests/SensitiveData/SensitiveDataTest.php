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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\SensitiveData;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;

class SensitiveDataTest extends TestCase
{
    public function sensitiveDataToSerialise()
    {
        return [
            'None' => [
                (new SensitiveData()),
                []
            ],
            'None, forgotten' => [
                (new SensitiveData())->forget(),
                []
            ],
            'CommonName' => [
                (new SensitiveData())->withCommonName(new CommonName('Willie')),
                ['CommonName' => new CommonName('Willie')],
            ],
            'CommonName, forgotten' => [
                (new SensitiveData())->withCommonName(new CommonName('Willie'))->forget(),
                ['CommonName' => CommonName::unknown()],
            ],
            'CommonName, Email' => [
                (new SensitiveData())
                    ->withCommonName(new CommonName('Willie'))
                    ->withEmail(new Email('johnny@flarge.invalid')),
                ['CommonName' => new CommonName('Willie'), 'Email' => new Email('johnny@flarge.invalid')],
            ],
            'CommonName, Email, forgotten' => [
                (new SensitiveData())
                    ->withCommonName(new CommonName('Willie'))
                    ->withEmail(new Email('johnny@flarge.invalid'))
                    ->forget(),
                ['CommonName' => CommonName::unknown(), 'Email' => Email::unknown()],
            ],
            'DocumentNumber' => [
                (new SensitiveData())
                    ->withDocumentNumber(new DocumentNumber('OVER-9000')),
                ['DocumentNumber' => new DocumentNumber('OVER-9000')],
            ],
            'DocumentNumber, forgotten' => [
                (new SensitiveData())
                    ->withDocumentNumber(new DocumentNumber('OVER-9000'))
                    ->forget(),
                ['DocumentNumber' => DocumentNumber::unknown()],
            ],
            'GssfId' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new GssfId('1234'), new SecondFactorType('tiqr')),
                ['SecondFactorIdentifier' => new GssfId('1234')],
            ],
            'GssfId, forgotten' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new GssfId('1234'), new SecondFactorType('tiqr'))
                    ->forget(),
                ['SecondFactorIdentifier' => GssfId::unknown()],
            ],
            'YubikeyPublicId' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new YubikeyPublicId('c'), new SecondFactorType('yubikey')),
                ['SecondFactorIdentifier' => new YubikeyPublicId('c')],
            ],
            'YubikeyPublicId, forgotten' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new YubikeyPublicId('c'), new SecondFactorType('yubikey'))
                    ->forget(),
                ['SecondFactorIdentifier' => YubikeyPublicId::unknown()],
            ],
        ];
    }

    /**
     * @test
     * @group sensitive-data
     * @dataProvider sensitiveDataToSerialise
     *
     * @param SensitiveData $sensitiveData
     * @param array         $getterExpectations
     */
    public function it_serialises_and_deserialises(
        SensitiveData $sensitiveData,
        array $getterExpectations
    ) {
        $sensitiveData = SensitiveData::deserialize(json_decode(json_encode($sensitiveData->serialize()), true));

        foreach ($getterExpectations as $data => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $sensitiveData->{"get$data"}(),
                "get$data() returned an unexpected value"
            );
        }
    }
}
