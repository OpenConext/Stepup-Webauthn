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

namespace App\Entity;

use Base64Url\Base64Url;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Webauthn\PublicKeyCredentialSource as BasePublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @ORM\Table(name="public_key_credential_sources")
 * @ORM\Entity(repositoryClass="App\Repository\PublicKeyCredentialSourceRepository")
 */
class PublicKeyCredentialSource extends BasePublicKeyCredentialSource
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $fmt;

    public function __construct(
        string $publicKeyCredentialId,
        string $type,
        array $transports,
        string $attestationType,
        TrustPath $trustPath,
        UuidInterface $aaguid,
        string $credentialPublicKey,
        string $userHandle,
        int $counter,
        string $fmt
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
        $this->fmt = $fmt;
    }

    public function getFmt(): string
    {
        return $this->fmt;
    }

    public function getId(): string
    {
        return $this->publicKeyCredentialId;
    }

    /**
     * This should be fixed in WebAuthn framework, mapping was incorrect.
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => Base64Url::encode($this->publicKeyCredentialId),
            'type' => $this->type,
            'transports' => $this->transports,
            'attestationType' => $this->attestationType,
            'trustPath' => $this->trustPath,
            'aaguid' => $this->aaguid->toString(),
            'credentialPublicKey' => Base64Url::encode($this->credentialPublicKey),
            'userHandle' => Base64Url::encode($this->userHandle),
            'counter' => $this->counter,
        ];
    }
}
