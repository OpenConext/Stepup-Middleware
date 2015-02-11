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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;

/**
 * Entity that contains the User Defined Settings that are relevant to the domain
 */
class ConfigurableSettings
{
    /**
     * @var EmailVerificationWindow
     */
    private $emailVerificationWindow;

    final private function __construct(EmailVerificationWindow $emailVerificationWindow)
    {
        $this->emailVerificationWindow = $emailVerificationWindow;
    }

    /**
     * @param int $emailVerificationWindow positive integer, amount of seconds the email verification window is open
     * @return ConfigurableSettings
     */
    public static function create($emailVerificationWindow)
    {
        return new self(EmailVerificationWindow::fromSeconds($emailVerificationWindow));
    }

    /**
     * @return EmailVerificationWindow
     */
    public function getEmailVerificationWindow()
    {
        return $this->emailVerificationWindow;
    }
}
