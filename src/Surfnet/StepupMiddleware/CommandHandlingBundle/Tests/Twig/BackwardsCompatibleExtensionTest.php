<?php

/**
 * Copyright 2024 SURFnet bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Twig;

use DateTime;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Twig\BackwardsCompatibleExtension;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;

/**
 * @requires extension intl
 */
class BackwardsCompatibleExtensionTest extends TestCase
{
    /**
     * @dataProvider templateProvider
 */
    public function testLocalizedData(string $template, string $expected, string $locale): void
    {
        $dateString = "2024-12-05 13:12:10";
        $date = new DateTime($dateString);
        $twig = new Environment(new ArrayLoader(['template' => $template]), ['debug' => true, 'cache' => false, 'autoescape' => 'html', 'optimizations' => 0]);
        $twig->addExtension( new BackwardsCompatibleExtension(new IntlExtension()));

        $output = $twig->render('template', ['date' => $date, 'locale' => $locale]);
        $this->assertEquals($expected, $output);

        $output = $twig->render('template', ['date' => $dateString, 'locale' => $locale]);
        $this->assertEquals($expected, $output);
    }

    public function templateProvider(): array
    {
        return [
            'date en' => ["{{ date | localizeddate('full', 'none', locale)  }}", 'Thursday, 5 December 2024', 'en_GB'],
            'date nl' => ["{{ date | localizeddate('full', 'none', locale)  }}", 'donderdag 5 december 2024', 'nl_NL'],
            'date and time nl' => ["{{ date | localizeddate('full', 'medium', locale)  }}", 'Thursday, 5 December 2024 at 13:12:10', 'en_GB'],
            'date and time en' => ["{{ date | localizeddate('full', 'medium', locale)  }}", 'donderdag 5 december 2024 om 13:12:10', 'nl_NL'],
            'time nl' => ["{{ date | localizeddate('none', 'medium', locale)  }}", '13:12:10', 'en_GB'],
            'time en' => ["{{ date | localizeddate('none', 'medium', locale)  }}", '13:12:10', 'nl_NL'],
        ];
    }
}