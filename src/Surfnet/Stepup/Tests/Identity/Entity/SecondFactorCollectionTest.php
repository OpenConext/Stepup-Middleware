<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\Stepup\Tests\Identity\Entity;

use Mockery as m;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Identity\Entity\SecondFactorCollection;
use Surfnet\Stepup\Identity\Entity\SecondFactor;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;

class SecondFactorCollectionTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function collection_can_return_second_factor_with_highest_loa()
    {
        $collection = new SecondFactorCollection([
            $this->mockVettedSecondFactor('sms'),
            $this->mockVettedSecondFactor('yubikey'),
        ]);

        $secondFactor = $collection->getSecondFactorWithHighestLoa(
            new SecondFactorTypeService([])
        );

        $this->assertNotNull($secondFactor, 'Collection should have returned a second factor object');
        $this->assertTrue($secondFactor->getType()->isYubikey(), 'Expected yubikey since it has a higher LoA than sms');
    }

    /**
     * @param string $type
     * @return SecondFactor
     */
    private function mockVettedSecondFactor($type)
    {
        $mock = m::mock('\Surfnet\Stepup\Identity\Entity\SecondFactor');
        $mock->shouldReceive('getType')
            ->andReturn(new SecondFactorType($type));

        return $mock;
    }
}
