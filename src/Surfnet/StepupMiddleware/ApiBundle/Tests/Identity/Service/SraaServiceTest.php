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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Identity\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;

final class SraaServiceTest extends TestCase
{
    #[Test]
    public function it_finds_sraa_by_name_id_when_present(): void
    {
        $service = new SraaService(['urn:collab:person:example.com:admin', 'urn:collab:person:example.com:other']);
        $result = $service->findByNameId(new NameId('urn:collab:person:example.com:admin'));

        $this->assertInstanceOf(Sraa::class, $result);
        $this->assertEquals('urn:collab:person:example.com:admin', (string) $result->nameId);
    }

    #[Test]
    public function it_returns_null_when_name_id_not_in_list(): void
    {
        $service = new SraaService(['urn:collab:person:example.com:admin']);
        $result = $service->findByNameId(new NameId('urn:collab:person:example.com:unknown'));

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_for_empty_sraa_list(): void
    {
        $service = new SraaService([]);
        $result = $service->findByNameId(new NameId('urn:collab:person:example.com:admin'));

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_all_sraas(): void
    {
        $nameIds = ['urn:collab:person:example.com:admin', 'urn:collab:person:example.com:other'];
        $service = new SraaService($nameIds);
        $result = $service->findAll();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Sraa::class, $result);
    }

    #[Test]
    public function it_returns_empty_array_when_no_sraas_configured(): void
    {
        $service = new SraaService([]);
        $result = $service->findAll();

        $this->assertSame([], $result);
    }

    #[Test]
    public function it_checks_if_name_id_is_sraa(): void
    {
        $service = new SraaService(['urn:collab:person:example.com:admin']);

        $this->assertTrue($service->contains(new NameId('urn:collab:person:example.com:admin')));
        $this->assertFalse($service->contains(new NameId('urn:collab:person:example.com:other')));
    }
}
