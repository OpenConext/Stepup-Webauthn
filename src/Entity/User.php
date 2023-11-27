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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends PublicKeyCredentialUserEntity implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=36)
     */
    public readonly string $id;

    /**
     * @Assert\Length(max = 100)
     */
    public readonly string $name;

    /**
     * @Assert\Length(max = 100)
     */
    public readonly string $displayName;

    /**
     * @var PublicKeyCredentialSource[]
     * @ORM\ManyToMany(targetEntity="App\Entity\PublicKeyCredentialSource")
     * @ORM\JoinTable(name="users_user_handles",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_handle", referencedColumnName="id", unique=true)}
     *      )
     */
    protected $publicKeyCredentialSources;

    public function __construct(string $id, string $name, string $displayName)
    {
        parent::__construct($name, $id, $displayName);
        $this->publicKeyCredentialSources = new ArrayCollection();
    }

    /**
     * WebAuthn project does not care about roles of any user.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): void
    {
    }

    public function getSalt(): void
    {
    }

    public function getUsername(): ?string
    {
        return $this->name;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function getPublicKeyCredentialSources(): array
    {
        return $this->publicKeyCredentialSources->getValues();
    }

    public function getUserIdentifier(): string
    {
        // TODO: Implement getUserIdentifier() method.
    }
}
