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

namespace App\Controller;

use App\Entity\PublicKeyCredentialSource;
use App\PublicKeyCredentialCreationOptionsStore;
use Assert\Assertion;
use Psr\Http\Message\ServerRequestInterface;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSourceRepository;

final class AttestationResponseController
{
    private $userEntityRepository;
    private $credentialSourceRepository;
    private $publicKeyCredentialLoader;
    private $attestationResponseValidator;
    private $store;
    private $registrationService;

    public function __construct(
        PublicKeyCredentialLoader $publicKeyCredentialLoader,
        AuthenticatorAttestationResponseValidator $attestationResponseValidator,
        PublicKeyCredentialUserEntityRepository $userEntityRepository,
        PublicKeyCredentialSourceRepository $credentialSourceRepository,
        PublicKeyCredentialCreationOptionsStore $store,
        RegistrationService $registrationService
    ) {
        $this->attestationResponseValidator = $attestationResponseValidator;
        $this->userEntityRepository = $userEntityRepository;
        $this->credentialSourceRepository = $credentialSourceRepository;
        $this->publicKeyCredentialLoader = $publicKeyCredentialLoader;
        $this->store = $store;
        $this->registrationService = $registrationService;
    }

    /**
     * Handles the attestation key response.
     *
     * @Route("/verify-attestation-response", methods={"POST"}, name="verify-attestation-response", )
     *
     * @param ServerRequestInterface $psr7Request
     * @param Request $request
     * @return Response
     */
    public function __invoke(ServerRequestInterface $psr7Request, Request $request): Response
    {
        if (!$this->registrationService->registrationRequired()) {
            return new JsonResponse(['status' => 'failed',  'errorMessage' => 'No registration required'], 400);
        }
        try {
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($request->getContent());
            $response = $publicKeyCredential->getResponse();
            Assertion::isInstanceOf($response, AuthenticatorAttestationResponse::class, 'Invalid response');
            $publicKeyCredentialCreationOptions = $this->store->get();
            $this->attestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);
            $this->userEntityRepository->saveUserEntity($publicKeyCredentialCreationOptions->getUser());
            $credentialSource = PublicKeyCredentialSource::create(
                $publicKeyCredential,
                $publicKeyCredentialCreationOptions->getUser()->getId()
            );
            $this->credentialSourceRepository->saveCredentialSource($credentialSource);
            // Set nameId of user.
            $this->registrationService->register($publicKeyCredentialCreationOptions->getUser()->getId());
            // Clear public key credentials options.
            $this->store->clear();
            return new JsonResponse(['status' => 'ok', 'errorMessage' => null]);
        } catch (Throwable $throwable) {
            return new JsonResponse(['status' => 'failed', 'errorMessage' => $throwable->getMessage()], 400);
        }
    }
}
