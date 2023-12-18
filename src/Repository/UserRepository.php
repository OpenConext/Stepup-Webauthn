<?php

/**
 * Copyright 2019 SURFnet B.V.
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

namespace Surfnet\Webauthn\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;
use Surfnet\Webauthn\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use LogicException;
use Webauthn\Bundle\Repository\CanRegisterUserEntity;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class UserRepository implements ServiceEntityRepositoryInterface, CanRegisterUserEntity, PublicKeyCredentialUserEntityRepositoryInterface
{
    private EntityManagerInterface $manager;

    public function __construct(ManagerRegistry $registry)
    {
        /** @var EntityManagerInterface $manager */
        $manager = $registry->getManagerForClass(User::class);

        if (is_null($manager)) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                User::class
            ));
        }
        $this->manager = $manager;
    }

    public function save(User $user): void
    {
        $this->manager->persist($user);
        $this->manager->flush();
    }

    public function getByUserId(string $id): User
    {
        return $this->manager->find(User::class, $id);
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.name = :name')
            ->setParameter(':name', $username)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('u')
            ->from(User::class, 'u')
            ->where('u.user_handle = :user_handle')
            ->setParameter(':user_handle', $userHandle)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function createUser(string $displayName) : PublicKeyCredentialUserEntity
    {
        $id = Uuid::uuid4()->toString();
        return new User($id, $id, $displayName);
    }

    public function createUserEntity(string $username, string $displayName, ?string $icon) : PublicKeyCredentialUserEntity
    {
        $id = Uuid::uuid4()->toString();
        return new User($id, $id, $displayName);
    }

    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity) : void
    {
        $this->manager->persist($userEntity);
        $this->manager->flush();
    }

    public function generateNextUserEntityId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
