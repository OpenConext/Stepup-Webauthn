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

use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\Bundle\Security\Handler\DefaultCreationOptionsHandler;

final readonly class AttestationRequestController
{
    public function __construct(
        private PublicKeyCredentialCreationOptionsStore $creationOptionsStore,
        private DefaultCreationOptionsHandler $creationOptionsHandler
    ) {
    }

    /**
     * Handles the attestation public key request.
     */
    #[Route(path: '/attestation-options', name: 'attestation-options', methods: ['POST'])]
    public function action(Request $request): Response
    {
            $publicKeyCredentialCreationOptions = $this->creationOptionsStore->get();
            $userEntity = $publicKeyCredentialCreationOptions->user;

            $response = $this->creationOptionsHandler->onCreationOptions(
                $publicKeyCredentialCreationOptions,
                $userEntity
            );

            return $response;
    }
    //TODO: add failurehandler
}
