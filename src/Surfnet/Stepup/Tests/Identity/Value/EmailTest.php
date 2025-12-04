<?php

declare(strict_types=1);

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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\Email;

class EmailTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[DataProvider('invalidArgumentProvider')]
    #[Group('domain')]
    public function the_email_address_must_be_a_non_empty_string(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email($invalidValue);
    }

    #[Test]
    #[DataProvider('invalidEmailProvider')]
    #[Group('domain')]
    public function the_email_address_given_must_be_rfc_822_compliant(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email($invalidValue);
    }

    #[Test]
    #[Group('domain')]
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

    public static function invalidArgumentProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
        ];
    }

    /**
     * provider for {@see the_email_address_given_must_be_rfc_822_compliant()}
     * This is by no means meant to be an exhaustive provider as we rely on PHP's filter_var for catching the invalid
     * variants, merely testing the fact that it is indeed checked.
     *
     * @return array
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'no @-sign' => ['mailboxexample.invalid'],
            'no tld' => ['mailbox@example'],
            'no mailbox' => ['@example.invalid'],
            'invalid mailbox' => ['(｡◕‿◕｡)@example.invalid'],
        ];
    }
}
