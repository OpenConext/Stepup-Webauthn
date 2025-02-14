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

namespace Surfnet\Webauthn\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Surfnet\Webauthn\Entity\PublicKeyCredentialSource as PublicKeyCredentialSourceEntity;
use Surfnet\Webauthn\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends PublicKeyCredentialUserEntity implements UserInterface
{
     #[ORM\Id]
     #[ORM\Column(type:"string", length:36, unique: true)]
     #[ORM\GeneratedValue(strategy: "NONE")]
    public readonly string $id;

    #[Assert\Length(max: 100)]
    public readonly string $name;

    #[Assert\Length(max: 100)]
    public readonly string $displayName;

    /**
     * WebAuthn project does not care about roles of any user.
     */
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): void
    {
    }

    public function eraseCredentials(): void
    {
    }


    public function getUserIdentifier(): string
    {
        return 'id';
    }
}
