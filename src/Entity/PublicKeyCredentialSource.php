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

use Assert\Assertion;
use Base64Url\Base64Url;
use Doctrine\ORM\Mapping as ORM;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource as BasePublicKeyCredentialSource;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
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

    public function getId(): string
    {
        return $this->publicKeyCredentialId;
    }

    public static function create(PublicKeyCredential $publicKeyCredential, string $userHandle): self
    {
        $response = $publicKeyCredential->getResponse();
        Assertion::isInstanceOf(
            $response,
            AuthenticatorAttestationResponse::class,
            'This method is only available with public key credential containing an authenticator attestation response.'
        );
        $publicKeyCredentialDescriptor = $publicKeyCredential->getPublicKeyCredentialDescriptor([
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_INTERNAL,
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_USB,
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_BLE,
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_NFC
        ]);
        $attestationStatement = $response->getAttestationObject()->getAttStmt();
        $authenticatorData = $response->getAttestationObject()->getAuthData();
        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential data available');
        return new static(
            $publicKeyCredentialDescriptor->getId(),
            $publicKeyCredentialDescriptor->getType(),
            $publicKeyCredentialDescriptor->getTransports(),
            $attestationStatement->getType(),
            $attestationStatement->getTrustPath(),
            $attestedCredentialData->getAaguid(),
            $attestedCredentialData->getCredentialPublicKey(),
            $userHandle,
            $authenticatorData->getSignCount()
        );
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
