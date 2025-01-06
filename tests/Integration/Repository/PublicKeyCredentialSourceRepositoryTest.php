<?php
/**
 * Copyright 2024 SURFnet B.V.
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

namespace Test\Integration\Repository;

use Doctrine\ORM\EntityManager;
use Surfnet\Webauthn\Entity\PublicKeyCredentialSource;
use Surfnet\Webauthn\Entity\User;
use Surfnet\Webauthn\Repository\PublicKeyCredentialSourceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Webauthn\TrustPath\EmptyTrustPath;

class PublicKeyCredentialSourceRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }


    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testRepo()
    {
        /** @var PublicKeyCredentialSourceRepository $repo */
        $repo = $this->entityManager->getRepository(PublicKeyCredentialSource::class);

        $item = new PublicKeyCredentialSource(
            'id',
            'type',
            ['transports'],
            'attestationType',
            new EmptyTrustPath(),
            new Uuid('580c810d-d82f-43ce-9796-6fd000be454a'),
            'credentialPublicKey',
            'userHandle',
            1,
            'fmt'
        );

        $repo->saveCredentialSource($item);

        $result = $repo->allForUser(new User('userHandle', 'foo', 'bar'));

        $this->assertNotEmpty($result);
    }

}
