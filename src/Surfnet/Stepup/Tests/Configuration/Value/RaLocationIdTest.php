<?php

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\RaLocationId;

class RaLocationIdTest extends TestCase
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function an_ra_location_id_cannot_be_created_with_anything_but_a_nonempty_string($invalidValue)
    {
        new RaLocationId($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_ids_with_the_same_values_are_equal()
    {
        $raLocationId = new RaLocationId('a');
        $theSame      = new RaLocationId('a');

        $this->assertTrue($raLocationId->equals($theSame));
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_ids_with_different_values_are_not_equal()
    {
        $raLocationId = new RaLocationId('a');
        $different    = new RaLocationId('A');

        $this->assertFalse($raLocationId->equals($different));
    }

    public function invalidValueProvider()
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}
