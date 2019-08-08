<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace App\Repository;

use Ramsey\Uuid\Uuid;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository as BasePublicKeyCredentialUserEntityRepository;

final class UserRepository implements ServiceEntityRepositoryInterface, BasePublicKeyCredentialUserEntityRepository
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $manager = $registry->getManagerForClass(User::class);

        if (null === $manager) {
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

    public function createUserEntity(string $username,string $displayName,?string $icon) : PublicKeyCredentialUserEntity
    {
        return new User(Uuid::uuid4()->toString(), $username,$displayName);
    }

    public function saveUserEntity(PublicKeyCredentialUserEntity $userEntity) : void
    {
        $this->manager->persist($userEntity);
        $this->manager->flush();
    }
}
