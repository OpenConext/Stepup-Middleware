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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Cache;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Cache\EmailTemplatesWarmer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class EmailTemplatesWarmerTest extends TestCase
{
    private string $templatesDir;

    private const LOCALES = ['en_GB', 'nl_NL'];
    private const TEMPLATE_NAMES = [
        'confirm_email',
        'registration_code_with_ras',
        'registration_code_with_ra_locations',
        'vetted',
        'second_factor_revoked',
        'second_factor_verification_reminder_with_ras',
        'second_factor_verification_reminder_with_ra_locations',
        'recovery_token_created',
        'recovery_token_revoked',
    ];

    protected function setUp(): void
    {
        $this->templatesDir = sys_get_temp_dir() . '/email_warmer_test_' . uniqid();
        foreach (self::LOCALES as $locale) {
            mkdir($this->templatesDir . '/' . $locale, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesDir);
    }

    #[Test]
    public function it_passes_when_all_templates_exist_and_compile(): void
    {
        $this->createAllTemplates('<p>Hello {{ name }}</p>');

        $warmer = new EmailTemplatesWarmer($this->createTwig(), $this->templatesDir, self::LOCALES);
        $warmer->warmUp('/tmp');

        // No exception = pass
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_throws_when_template_file_is_missing(): void
    {
        $this->createAllTemplates('<p>Hello</p>');
        unlink($this->templatesDir . '/en_GB/confirm_email.html.twig');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/missing/i');

        $warmer = new EmailTemplatesWarmer($this->createTwig(), $this->templatesDir, self::LOCALES);
        $warmer->warmUp('/tmp');
    }

    #[Test]
    public function it_throws_when_template_has_invalid_twig_syntax(): void
    {
        $this->createAllTemplates('<p>Hello</p>');
        file_put_contents(
            $this->templatesDir . '/en_GB/confirm_email.html.twig',
            '{% unclosed block',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/compile/i');

        $warmer = new EmailTemplatesWarmer($this->createTwig(), $this->templatesDir, self::LOCALES);
        $warmer->warmUp('/tmp');
    }

    #[Test]
    public function it_is_not_optional(): void
    {
        $warmer = new EmailTemplatesWarmer($this->createTwig(), $this->templatesDir, self::LOCALES);
        $this->assertFalse($warmer->isOptional());
    }

    private function createAllTemplates(string $content): void
    {
        foreach (self::LOCALES as $locale) {
            foreach (self::TEMPLATE_NAMES as $name) {
                file_put_contents(
                    sprintf('%s/%s/%s.html.twig', $this->templatesDir, $locale, $name),
                    $content,
                );
            }
        }
    }

    private function createTwig(): Environment
    {
        return new Environment(new ArrayLoader([]));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
