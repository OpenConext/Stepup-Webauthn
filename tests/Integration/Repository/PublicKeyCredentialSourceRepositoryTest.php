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
use Surfnet\Webauthn\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\PublicKeyCredentialSource as WebauthnPublicKeyCredentialSource;

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
        $credentialRepo = $this->entityManager->getRepository(PublicKeyCredentialSource::class);

        $userRepo = $this->entityManager->getRepository(User::class);

        $id = '8e501762-7cd6-4229-a2e6-c1daed8fd4ac';
        $nameId = '9e501762-7cd6-4229-a2e6-c1daed8fd4ac';
        $user = $userRepo->findOneByUsername($nameId);
        if (!$user) {
            $user = new User($id, $nameId, 'name');
            $userRepo->save($user);
        }

        $credentials = $credentialRepo->findAllForUserEntity($user);
        foreach ($credentials as $credential) {
            $credentialRepo->createQueryBuilder('c')
                ->delete()
                ->where('c.id = :id')
                ->setParameter('id', $credential->getId())
                ->getQuery()
                ->execute();
        }

        $credential = new WebauthnPublicKeyCredentialSource(
            'id',
            'type',
            ['transports'],
            'attestationType',
            new EmptyTrustPath(),
            new Uuid('580c810d-d82f-43ce-9796-6fd000be454a'),
            'credentialPublicKey',
            $user->id,
            1,
            ['fmt'],
            1,
            1,
            1,
        );

        $credentialRepo->saveCredentialSource($credential);

        $result = $credentialRepo->findAllForUserEntity($user);

        $this->assertNotEmpty($result);
        $this->assertSame($user->id, $result[0]->userHandle);
    }

    public function testRepoIdempotent() {
        $this->testRepo();
    }

}
