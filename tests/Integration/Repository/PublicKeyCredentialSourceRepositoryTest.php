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

declare(strict_types=1);

namespace Test\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Surfnet\Webauthn\Entity\PublicKeyCredentialSource;
use Surfnet\Webauthn\Entity\User;
use Surfnet\Webauthn\Repository\PublicKeyCredentialSourceRepository;
use Surfnet\Webauthn\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource as WebauthnPublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

class PublicKeyCredentialSourceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PublicKeyCredentialSourceRepository $credentialRepo;
    private UserRepository $userRepo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->credentialRepo = $this->getContainer()->get(PublicKeyCredentialSourceRepository::class);
        $this->userRepo = $this->getContainer()->get(UserRepository::class);
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        $this->entityManager->close();
        parent::tearDown();
    }

    /**
     * A base WebauthnPublicKeyCredentialSource passed to saveCredentialSource() must
     * be wrapped in our entity subclass before persisting.
     */
    public function testSaveWrapsBaseCredentialSourceInOurEntity(): void
    {
        $user = $this->createUser();

        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id));

        $results = $this->credentialRepo->findAllForUserEntity($user);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(PublicKeyCredentialSource::class, $results[0]);
    }

    /**
     * When our entity subclass is passed directly it must be persisted as-is
     * without double-wrapping.
     */
    public function testSaveOurEntitySubclassDirectly(): void
    {
        $user = $this->createUser();
        $entity = $this->buildOurEntity($user->id, 'direct_cred_id');

        $this->credentialRepo->saveCredentialSource($entity);

        $results = $this->credentialRepo->findAllForUserEntity($user);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(PublicKeyCredentialSource::class, $results[0]);
    }

    /**
     * findAllForUserEntity() returns the single credential saved for the user.
     */
    public function testFindAllForUserEntityReturnsSavedCredential(): void
    {
        $user = $this->createUser();
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id, 'cred_id'));

        $results = $this->credentialRepo->findAllForUserEntity($user);

        $this->assertCount(1, $results);
        $this->assertSame($user->id, $results[0]->userHandle);
    }

    /**
     * findAllForUserEntity() returns an empty array when no credentials exist
     * for the user.
     */
    public function testFindAllForUserEntityReturnsEmptyArrayWhenNoCredentials(): void
    {
        $user = $this->createUser();

        $results = $this->credentialRepo->findAllForUserEntity($user);

        $this->assertSame([], $results);
    }

    /**
     * findAllForUserEntity() returns all credentials when a user has multiple.
     */
    public function testFindAllForUserEntityReturnsAllCredentialsForUser(): void
    {
        $user = $this->createUser();
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id, 'cred_a'));
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id, 'cred_b'));
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id, 'cred_c'));

        $results = $this->credentialRepo->findAllForUserEntity($user);

        $this->assertCount(3, $results);
    }

    /**
     * Credentials saved for user A must not appear when querying for user B.
     */
    public function testFindAllForUserEntityDoesNotLeakOtherUsersCredentials(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($userA->id, 'cred_a'));
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($userB->id, 'cred_b'));

        $resultsA = $this->credentialRepo->findAllForUserEntity($userA);
        $resultsB = $this->credentialRepo->findAllForUserEntity($userB);

        $this->assertCount(1, $resultsA);
        $this->assertSame($userA->id, $resultsA[0]->userHandle);
        $this->assertCount(1, $resultsB);
        $this->assertSame($userB->id, $resultsB[0]->userHandle);
    }

    /**
     * findOneByCredentialId() returns the credential matching the given raw
     * credential ID (the repository handles base64-encoding internally).
     */
    public function testFindOneByCredentialIdReturnsSavedCredential(): void
    {
        $user = $this->createUser();
        $rawId = 'my_webauthn_credential_id';
        $this->credentialRepo->saveCredentialSource($this->buildBaseCredential($user->id, $rawId));

        $result = $this->credentialRepo->findOneByCredentialId($rawId);

        $this->assertNotNull($result);
        $this->assertSame($rawId, $result->publicKeyCredentialId);
        $this->assertSame($user->id, $result->userHandle);
    }

    /**
     * findOneByCredentialId() returns null when no credential with the given
     * ID is stored.
     */
    public function testFindOneByCredentialIdReturnsNullForUnknownId(): void
    {
        $result = $this->credentialRepo->findOneByCredentialId('does_not_exist');

        $this->assertNull($result);
    }

    /**
     * The credential counter value must be preserved when round-tripping
     * through the database.
     */
    public function testCredentialCounterIsPersistedCorrectly(): void
    {
        $user = $this->createUser();
        $credential = $this->buildBaseCredential($user->id, 'counter_cred', counter: 42);
        $this->credentialRepo->saveCredentialSource($credential);

        $result = $this->credentialRepo->findOneByCredentialId('counter_cred');

        $this->assertNotNull($result);
        $this->assertSame(42, $result->counter);
    }

    private function createUser(): User
    {
        $id = Uuid::v4()->toRfc4122();
        $user = new User($id, $id, 'Test User');
        $this->userRepo->save($user);
        return $user;
    }

    private function buildBaseCredential(
        string $userHandle,
        string $credentialId = 'default_cred_id',
        int $counter = 0,
    ): WebauthnPublicKeyCredentialSource {
        return new WebauthnPublicKeyCredentialSource(
            $credentialId,
            'public-key',
            [],
            'none',
            new EmptyTrustPath(),
            new Uuid('580c810d-d82f-43ce-9796-6fd000be454a'),
            'publicKey',
            $userHandle,
            $counter,
        );
    }

    private function buildOurEntity(string $userHandle, string $credentialId): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $credentialId,
            'public-key',
            [],
            'none',
            new EmptyTrustPath(),
            new Uuid('580c810d-d82f-43ce-9796-6fd000be454a'),
            'publicKey',
            $userHandle,
            0,
            'none',
        );
    }
}
