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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Surfnet\Webauthn\Repository\PublicKeyCredentialSourceRepository;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource as BasePublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
#[ORM\Table(name:"public_key_credential_sources")]
#[ORM\Entity(repositoryClass: PublicKeyCredentialSourceRepository::class)]
class PublicKeyCredentialSource extends BasePublicKeyCredentialSource
{
    #[ORM\Id]
    #[ORM\Column(type:Types::STRING, length:36, unique: true)]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private string $id;

    /**
     * Override the $backupEligible, $backupStatus and $uvInitialized fields which we do not use, but needs
     * to be initialized. Needed to prevent read before written errors.
     */
    public ?bool $backupEligible = null;
    public ?bool $backupStatus = null;
    public ?bool $uvInitialized = false;

    public function __construct(
        string $publicKeyCredentialId,
        string $type,
        array $transports,
        string $attestationType,
        TrustPath $trustPath,
        Uuid $aaguid,
        string $credentialPublicKey,
        string $userHandle,
        int $counter,
        #[ORM\Column(type: Types::STRING)]
        private string $fmt
    ) {
        $this->id = Uuid::v4()->toRfc4122();
        parent::__construct(
            $publicKeyCredentialId,
            $type,
            $transports,
            $attestationType,
            $trustPath,
            $aaguid,
            $credentialPublicKey,
            $userHandle,
            $counter
        );
    }

    public function getId(): string
    {
        return $this->id;
    }
}
