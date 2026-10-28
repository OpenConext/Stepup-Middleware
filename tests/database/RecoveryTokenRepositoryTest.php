<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupMiddleware\Test\Database;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RecoveryToken;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RecoveryTokenRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecoveryTokenRepositoryTest extends KernelTestCase
{
    private RecoveryTokenRepository $repository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $entityManager = $container->get('doctrine')->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
        $this->repository = $container->get(RecoveryTokenRepository::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $this->saveIdentity('forgotten', CommonName::unknown(), Email::unknown());
        $this->saveIdentity('restored', new CommonName('Eeva Kuopio'), new Email('e.kuopio@hy.fi'));
        $this->saveRecoveryToken('token-of-forgotten-1', 'forgotten');
        $this->saveRecoveryToken('token-of-forgotten-2', 'forgotten');
        $this->saveRecoveryToken('token-of-restored', 'restored');
        $this->entityManager->flush();
    }

    public function test_it_counts_only_recovery_tokens_of_forgotten_identities(): void
    {
        $this->assertSame(2, $this->repository->countForForgottenIdentities());
    }

    public function test_it_removes_only_recovery_tokens_of_forgotten_identities(): void
    {
        $this->assertSame(2, $this->repository->removeForForgottenIdentities());

        $this->entityManager->clear();
        $remaining = array_map(
            static fn(RecoveryToken $token): string => $token->id,
            $this->repository->findAll(),
        );
        $this->assertSame(['token-of-restored'], $remaining);
        $this->assertSame(0, $this->repository->removeForForgottenIdentities());
    }

    private function saveIdentity(string $id, CommonName $commonName, Email $email): void
    {
        $this->entityManager->persist(Identity::create(
            $id,
            new Institution('institution-a.nl'),
            new NameId('urn:' . $id),
            $email,
            $commonName,
            new Locale('en_GB'),
        ));
    }

    private function saveRecoveryToken(string $id, string $identityId): void
    {
        $token = new RecoveryToken();
        $token->id = $id;
        $token->identityId = $identityId;
        $token->type = 'sms';
        $token->status = RecoveryTokenStatus::active();
        $token->institution = new Institution('institution-a.nl');
        $token->name = new CommonName('Eeva Kuopio');
        $token->email = new Email('e.kuopio@hy.fi');
        $token->recoveryMethodIdentifier = '+31612345678';
        $this->entityManager->persist($token);
    }
}
