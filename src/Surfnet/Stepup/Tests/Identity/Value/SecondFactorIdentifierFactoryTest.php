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

namespace Surfnet\Stepup\Tests\Identity\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\Stepup\Identity\Value\U2fKeyHandle;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;

final class SecondFactorIdentifierFactoryTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function generates_identifiers_of_all_types()
    {
        $this->assertEquals(
            new PhoneNumber('+31 (0) 12345678'),
            SecondFactorIdentifierFactory::forType(new SecondFactorType('sms'), '+31 (0) 12345678')
        );
        $this->assertEquals(
            new YubikeyPublicId('08189273'),
            SecondFactorIdentifierFactory::forType(new SecondFactorType('yubikey'), '08189273')
        );
        $this->assertEquals(
            new GssfId('urn:abcd-efgh-ijkl'),
            SecondFactorIdentifierFactory::forType(new SecondFactorType('tiqr'), 'urn:abcd-efgh-ijkl')
        );
        $this->assertEquals(
            new U2fKeyHandle('PWaaP_Beep'),
            SecondFactorIdentifierFactory::forType(new SecondFactorType('u2f'), 'PWaaP_Beep')
        );

        $this->assertEquals(
            PhoneNumber::unknown(),
            SecondFactorIdentifierFactory::unknownForType(new SecondFactorType('sms'))
        );
        $this->assertEquals(
            YubikeyPublicId::unknown(),
            SecondFactorIdentifierFactory::unknownForType(new SecondFactorType('yubikey'))
        );
        $this->assertEquals(
            GssfId::unknown(),
            SecondFactorIdentifierFactory::unknownForType(new SecondFactorType('tiqr'))
        );
        $this->assertEquals(
            U2fKeyHandle::unknown(),
            SecondFactorIdentifierFactory::unknownForType(new SecondFactorType('u2f'))
        );
    }
}
