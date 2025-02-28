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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\SensitiveData;
use function is_string;

class SensitiveDataTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function sensitiveDataToSerialise(): array
    {
        return [
            'None' => [
                (new SensitiveData()),
                [],
            ],
            'None, forgotten' => [
                (new SensitiveData())->forget(),
                [],
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
                    ->withSecondFactorIdentifier(new YubikeyPublicId('00177273'), new SecondFactorType('yubikey')),
                ['SecondFactorIdentifier' => new YubikeyPublicId('00177273')],
            ],
            'YubikeyPublicId, forgotten' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new YubikeyPublicId('00177273'), new SecondFactorType('yubikey'))
                    ->forget(),
                ['SecondFactorIdentifier' => YubikeyPublicId::unknown()],
            ],
            'VettingType' => [
                (new SensitiveData())
                    ->withVettingType(new OnPremiseVettingType(new DocumentNumber("012345678"))),
                ['VettingType' => new OnPremiseVettingType(new DocumentNumber("012345678"))],
            ],
            'VettingType, forgotten' => [
                (new SensitiveData())
                    ->withSecondFactorIdentifier(new YubikeyPublicId('00177273'), new SecondFactorType('yubikey'))
                    ->forget(),
                ['VettingType' => new UnknownVettingType()],
            ],
        ];
    }

    /**
     * @test
     * @group sensitive-data
     * @dataProvider sensitiveDataToSerialise
     */
    public function it_serialises_and_deserialises(
        SensitiveData $sensitiveData,
        array $getterExpectations,
    ): void {
        $serializedData = json_encode($sensitiveData->serialize());
        if (!is_string($serializedData)) {
            $this->fail('Unable to json_encode the serialized sensitive data');
        }
        $sensitiveData = SensitiveData::deserialize(json_decode($serializedData, true));

        foreach ($getterExpectations as $data => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $sensitiveData->{"get$data"}(),
                "get$data() returned an unexpected value",
            );
        }

        $this->assertInstanceOf(SensitiveData::class, $sensitiveData);
    }
}
