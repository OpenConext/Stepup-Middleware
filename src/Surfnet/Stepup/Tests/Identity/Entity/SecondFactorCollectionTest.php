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
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Entity\SecondFactor;
use Surfnet\Stepup\Identity\Entity\SecondFactorCollection;
use Surfnet\Stepup\Identity\Entity\VettedSecondFactor;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;

class SecondFactorCollectionTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function collection_can_return_second_factor_with_highest_loa(): void
    {
        $collection = new SecondFactorCollection([
            $this->mockVettedSecondFactor('sms'),
            $this->mockVettedSecondFactor('yubikey'),
        ]);

        $secondFactor = $collection->getSecondFactorWithHighestLoa(
            new SecondFactorTypeService([]),
        );

        $this->assertNotNull($secondFactor, 'Collection should have returned a second factor object');
        $this->assertTrue($secondFactor->getType()->isYubikey(), 'Expected yubikey since it has a higher LoA than sms');
    }

    /**
     * @return SecondFactor
     */
    private function mockVettedSecondFactor(string $type)
    {
        $mock = m::mock(VettedSecondFactor::class);
        $mock->shouldReceive('getType')
            ->andReturn(new SecondFactorType($type));
        $mock->shouldReceive('vettingType')
            ->andReturn(new OnPremiseVettingType(new DocumentNumber('123123')));

        return $mock;
    }
}
