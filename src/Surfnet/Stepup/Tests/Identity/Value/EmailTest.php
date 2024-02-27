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

use PHPUnit\Framework\TestCase as UnitTest;
use StdClass;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\Email;

class EmailTest extends UnitTest
{
    /**
     * @test
     * @group domain
     * @dataProvider invalidArgumentProvider
     *
     * @param mixed $invalidValue
     */
    public function the_email_address_must_be_a_non_empty_string(string|int|float|StdClass|array $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email($invalidValue);
    }

    /**
     * @test
     * @group domain
     * @dataProvider invalidEmailProvider
     * @param $invalidValue
     */
    public function the_email_address_given_must_be_rfc_822_compliant(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_emails_with_the_same_value_are_equal(): void
    {
        $email = new Email('email@example.invalid');
        $theSame = new Email('email@example.invalid');
        $different = new Email('different@example.invalid');
        $unknown = Email::unknown();

        $this->assertTrue($email->equals($theSame));
        $this->assertFalse($email->equals($different));
        $this->assertFalse($email->equals($unknown));
    }

    /**
     * provider for {@see the_email_address_must_be_a_non_empty_string()}
     */
    public function invalidArgumentProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array' => [[]],
            'integer' => [1],
            'float' => [1.2],
            'object' => [new StdClass()],
        ];
    }

    /**
     * provider for {@see the_email_address_given_must_be_rfc_822_compliant()}
     * This is by no means meant to be an exhaustive provider as we rely on PHP's filter_var for catching the invalid
     * variants, merely testing the fact that it is indeed checked.
     *
     * @return array
     */
    public function invalidEmailProvider(): array
    {
        return [
            'no @-sign' => ['mailboxexample.invalid'],
            'no tld' => ['mailbox@example'],
            'no mailbox' => ['@example.invalid'],
            'invalid mailbox' => ['(｡◕‿◕｡)@example.invalid'],
        ];
    }
}
