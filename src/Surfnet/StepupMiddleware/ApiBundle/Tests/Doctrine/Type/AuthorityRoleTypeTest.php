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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Doctrine\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\AuthorityRoleType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;

class CommonNameTypeTest extends UnitTest
{
    /**
     * @var \Doctrine\DBAL\Platforms\MySqlPlatform
     */
    private $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass()
    {
        Type::addType(AuthorityRoleType::NAME, 'Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\AuthorityRoleType');
    }

    public function setUp()
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion()
    {
        $authorityRole = Type::getType(AuthorityRoleType::NAME);

        $value = $authorityRole->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format()
    {
        $authorityRole = Type::getType(AuthorityRoleType::NAME);

        $input  = AuthorityRole::RAA();
        $output = $authorityRole->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals(AuthorityRole::ROLE_RAA, $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value()
    {
        $authorityRole = Type::getType(AuthorityRoleType::NAME);

        $value = $authorityRole->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_an_authority_role_value_object()
    {
        $authorityRole = Type::getType(AuthorityRoleType::NAME);

        $input = AuthorityRole::ROLE_RA;

        $output = $authorityRole->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf('Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole', $output);
        $this->assertEquals(new AuthorityRole($input), $output);
    }

    /**
     * @test
     * @group doctrine
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion()
    {
        $authorityRole = Type::getType(AuthorityRoleType::NAME);

        $authorityRole->convertToPHPValue(false, $this->platform);
    }
}
