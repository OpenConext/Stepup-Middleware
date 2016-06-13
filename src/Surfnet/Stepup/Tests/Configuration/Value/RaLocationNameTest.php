<?php

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocationNameTest extends TestCase
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function an_ra_location_name_cannot_be_created_with_anything_but_a_nonempty_string($invalidValue)
    {
        new RaLocationName($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_names_with_the_same_values_are_equal()
    {
        $raLocationName = new RaLocationName('a');
        $theSame      = new RaLocationName('a');

        $this->assertTrue($raLocationName->equals($theSame));
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_names_with_different_values_are_not_equal()
    {
        $raLocationName = new RaLocationName('a');
        $different    = new RaLocationName('A');

        $this->assertFalse($raLocationName->equals($different));
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
