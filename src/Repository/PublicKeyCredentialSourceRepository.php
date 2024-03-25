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

use Surfnet\Webauthn\Entity\PublicKeyCredentialSource;
use Surfnet\Webauthn\Entity\User;
use Assert\Assertion;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Bundle\Repository\DoctrineCredentialSourceRepository;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;

/**
 * @extends DoctrineCredentialSourceRepository<PublicKeyCredentialSource>
 */
class PublicKeyCredentialSourceRepository extends DoctrineCredentialSourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicKeyCredentialSource::class);
    }

    public function create(PublicKeyCredential $publicKeyCredential, string $userHandle): PublicKeyCredentialSource
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
        /** @var AttestationObject $attestationObject */
        $attestationObject = $response->getAttestationObject();
        $attestationStatement = $attestationObject->getAttStmt();
        $authenticatorData = $attestationObject->getAuthData();
        $attestedCredentialData = $authenticatorData->getAttestedCredentialData();
        Assertion::notNull($attestedCredentialData, 'No attested credential data available');
        return new PublicKeyCredentialSource(
            $publicKeyCredentialDescriptor->getId(),
            $publicKeyCredentialDescriptor->getType(),
            $publicKeyCredentialDescriptor->getTransports(),
            $attestationStatement->getType(),
            $attestationStatement->getTrustPath(),
            $attestedCredentialData->getAaguid(),
            $attestedCredentialData->getCredentialPublicKey(),
            $userHandle,
            $authenticatorData->getSignCount(),
            $attestationStatement->getFmt()
        );
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function allForUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb->select('c')
            ->from($this->getClass(), 'c')
            ->where('c.userHandle = :user_handle')
            ->setParameter(':user_handle', $user->getId())
            ->getQuery()
            ->execute();
    }
}
