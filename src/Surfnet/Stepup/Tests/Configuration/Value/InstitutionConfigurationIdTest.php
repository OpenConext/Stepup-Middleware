<?php

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;

class InstitutionConfigurationIdTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function two_institution_configuration_ids_created_for_the_different_institution_are_not_equal()
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $different = InstitutionConfigurationId::from(new Institution('A different institution'));

        $this->assertNotEquals($institutionConfigurationId, $different);
    }

    /**
     * @test
     * @group domain
     */
    public function two_institution_configuration_ids_created_for_the_same_institution_are_equal()
    {
        $institutionConfigurationId = InstitutionConfigurationId::from(new Institution('An institution'));
        $same = InstitutionConfigurationId::from(new Institution('An institution'));

        $this->assertEquals($institutionConfigurationId, $same);
    }
}
