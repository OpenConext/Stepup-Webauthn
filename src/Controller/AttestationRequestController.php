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

namespace Surfnet\Webauthn\Controller;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Surfnet\Webauthn\ValidationJsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\Bundle\Security\Handler\DefaultCreationOptionsHandler;

final readonly class AttestationRequestController
{
    public function __construct(
        private PublicKeyCredentialCreationOptionsStore $creationOptionsStore,
        private DefaultCreationOptionsHandler $creationOptionsHandler,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handles the attestation public key request.
     */
    #[Route(path: '/attestation-options', name: 'attestation-options', methods: ['POST'])]
    public function action(): Response
    {
        $publicKeyCredentialCreationOptions = $this->creationOptionsStore->get();
        $userEntity = $publicKeyCredentialCreationOptions->user;

        try {
            $response = $this->creationOptionsHandler->onCreationOptions(
                $publicKeyCredentialCreationOptions,
                $userEntity
            );
        } catch (RuntimeException $e) {
            $this->logger->warning(sprintf('Unable to create the attestation options: "%s"', $e->getMessage()));
            return ValidationJsonResponse::failedAttestationRequest($e);
        }

        return $response;
    }
}
