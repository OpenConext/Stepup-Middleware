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

namespace Surfnet\StepupMiddleware\ManagementBundle\Tests\Configuration\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Surfnet\StepupMiddleware\ManagementBundle\Configuration\Service\DiskEmailTemplateService;

final class DiskEmailTemplateServiceTest extends TestCase
{
    private string $templatesDir;

    protected function setUp(): void
    {
        $this->templatesDir = sys_get_temp_dir() . '/disk_email_template_test_' . uniqid();
        mkdir($this->templatesDir . '/en_GB', 0777, true);
        mkdir($this->templatesDir . '/nl_NL', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesDir);
    }

    #[Test]
    public function it_returns_template_for_preferred_locale(): void
    {
        file_put_contents($this->templatesDir . '/en_GB/confirm_email.html.twig', '<p>Hello {{ commonName }}</p>');
        file_put_contents($this->templatesDir . '/nl_NL/confirm_email.html.twig', '<p>Hallo {{ commonName }}</p>');

        $service = new DiskEmailTemplateService($this->templatesDir);
        $template = $service->findByName('confirm_email', 'en_GB', 'nl_NL');

        $this->assertNotNull($template);
        $this->assertEquals('confirm_email', $template->name);
        $this->assertEquals('en_GB', $template->locale);
        $this->assertStringContainsString('Hello', $template->htmlContent);
    }

    #[Test]
    public function it_falls_back_to_fallback_locale_when_preferred_missing(): void
    {
        file_put_contents($this->templatesDir . '/nl_NL/confirm_email.html.twig', '<p>Hallo {{ commonName }}</p>');

        $service = new DiskEmailTemplateService($this->templatesDir);
        $template = $service->findByName('confirm_email', 'en_GB', 'nl_NL');

        $this->assertNotNull($template);
        $this->assertEquals('nl_NL', $template->locale);
        $this->assertStringContainsString('Hallo', $template->htmlContent);
    }

    #[Test]
    public function it_returns_null_when_template_missing_in_both_locales(): void
    {
        $service = new DiskEmailTemplateService($this->templatesDir);
        $template = $service->findByName('confirm_email', 'en_GB', 'nl_NL');

        $this->assertNull($template);
    }

    #[Test]
    public function it_throws_when_template_file_is_not_readable(): void
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test unreadable files as root');
        }

        $path = $this->templatesDir . '/en_GB/confirm_email.html.twig';
        file_put_contents($path, '<p>Hello</p>');
        chmod($path, 0000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not readable/i');

        $service = new DiskEmailTemplateService($this->templatesDir);
        $service->findByName('confirm_email', 'en_GB', 'nl_NL');
    }

    #[Test]
    public function it_returns_correct_html_content(): void
    {
        $content = '<p>Dear {{ commonName }}, your code is {{ registrationCode }}</p>';
        file_put_contents($this->templatesDir . '/en_GB/registration_code_with_ras.html.twig', $content);

        $service = new DiskEmailTemplateService($this->templatesDir);
        $template = $service->findByName('registration_code_with_ras', 'en_GB', 'nl_NL');

        $this->assertNotNull($template);
        $this->assertEquals($content, $template->htmlContent);
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
