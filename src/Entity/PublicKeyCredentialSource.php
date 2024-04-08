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

use Doctrine\ORM\Mapping as ORM;
use Surfnet\Webauthn\Exception\RuntimeException;
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
     #[ORM\GeneratedValue]
     #[ORM\Column(type:"integer")]
    private string $id;

    /**
     * Override the $uvInitialized field which we do not use, but needs
     * to be initialized. Needed to prevent read before written errors.
     */
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
        #[ORM\Column(type: "string")]
        private string $fmt
    ) {
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

    /**
     * Warning: the id field is accessed directly in :\Webauthn\CeremonyStep\CheckAllowedCredentialList::process
     * The entity tracks a numeric auto increment id value, but the CheckAllowedCredentialList expects the
     * publicKeyCredentialId.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'id') {
            return $this->publicKeyCredentialId;
        }
        throw new RuntimeException(sprintf('Not allowed to access "%s" via the magic __get function', $name));
    }

    public function getFmt(): string
    {
        return $this->fmt;
    }

    /**
     * This should be fixed in WebAuthn framework, mapping was incorrect.
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->base64UrlEncode($this->publicKeyCredentialId),
            'type' => $this->type,
            'transports' => $this->transports,
            'attestationType' => $this->attestationType,
            'trustPath' => $this->trustPath,
            'aaguid' => $this->aaguid->toBase32(),
            'credentialPublicKey' => $this->base64UrlEncode($this->credentialPublicKey),
            'userHandle' => $this->base64UrlEncode($this->userHandle),
            'counter' => $this->counter,
        ];
    }

    /**
     * Encode data to Base64URL
     * From: https://base64.guru/developers/php/examples/base64url
     */
    private function base64UrlEncode(string $data): string
    {
        // First of all you should encode $data to Base64 string
        $b64 = base64_encode($data);
        // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
        $url = strtr($b64, '+/', '-_');
        // Remove padding character from the end of line and return the Base64URL result
        return rtrim($url, '=');
    }
}
