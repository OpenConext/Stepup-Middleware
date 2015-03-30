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

use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\Email;

class EmailTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider invalidArgumentProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function the_email_address_must_be_a_non_empty_string($invalidValue)
    {
        new Email($invalidValue);
    }

    /**
     * @test
     * @group domain
     * @dataProvider invalidEmailProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     * @param $invalidValue
     */
    public function the_email_address_given_must_be_rfc_822_compliant($invalidValue)
    {
        new Email($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_emails_with_the_same_value_are_equal()
    {
        $email     = new Email('email@example.tld');
        $theSame   = new Email('email@example.tld');
        $different = new Email('different@example.tld');

        $this->assertTrue($email->equals($theSame));
        $this->assertFalse($email->equals($different));
    }

    /**
     * provider for {@see the_email_address_must_be_a_non_empty_string()}
     */
    public function invalidArgumentProvider()
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

    /**
     * provider for {@see the_email_address_given_must_be_rfc_822_compliant()}
     * This is by no means meant to be an exhaustive provider as we rely on PHP's filter_var for catching the invalid
     * variants, merely testing the fact that it is indeed checked.
     *
     * @return array
     */
    public function invalidEmailProvider()
    {
        return [
            'no @-sign'       => ['mailboxexample.tld'],
            'no tld'          => ['mailbox@example'],
            'no mailbox'      => ['@example.tld'],
            'invalid mailbox' => ['(｡◕‿◕｡)@example.tld'],
        ];
    }
}
