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

use Surfnet\Webauthn\Exception\NoActiveAuthenrequestException;
use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Surfnet\Webauthn\Repository\PublicKeyCredentialSourceRepository;
use Surfnet\Webauthn\Service\MetadataStatementService;
use Surfnet\Webauthn\ValidationJsonResponse;
use Surfnet\Webauthn\WithContextLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Repository\CanRegisterUserEntity;
use Webauthn\PublicKeyCredentialLoader;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class AttestationResponseController
{
    public function __construct(
        private PublicKeyCredentialLoader $publicKeyCredentialLoader,
        private AuthenticatorAttestationResponseValidator $attestationResponseValidator,
        private CanRegisterUserEntity $userRegistrationRepository,
        private PublicKeyCredentialSourceRepository $credentialSourceRepository,
        private MetadataStatementService $mds,
        private PublicKeyCredentialCreationOptionsStore $store,
        private RegistrationService $registrationService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handles the attestation public key response.
     */
    #[Route(path: '/attestation-verification', name: 'attestation-verification', methods: ['POST'])]
    public function action(Request $request): Response
    {
        $this->logger->info('Verifying if there is a pending registration from SP');

        if (!$this->registrationService->registrationRequired()) {
            $this->logger->warning('Registration is not required');
            return ValidationJsonResponse::noRegistrationRequired(new NoActiveAuthenrequestException());
        }

        $this->logger->info('Verify valid public key credential response');


        try {
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($request->getContent());
            $response = $publicKeyCredential->response;
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new UnrecoverableErrorException('Invalid response type');
            }
        } catch (Exception $e) {
            $this->logger->warning(sprintf('Invalid public key credential response "%s"', $e->getMessage()));
            return ValidationJsonResponse::reportErrorMessage($e);
        }

        $this->logger->info('Verify if there is an existing public key credential creation options in session');

        try {
            $publicKeyCredentialCreationOptions = $this->store->get();
        } catch (Exception $e) {
            $this->logger->warning('No pending public key credential creation options in session');
            return ValidationJsonResponse::reportErrorMessage($e);
        }

        $nameId = $publicKeyCredentialCreationOptions->user->id;
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        $logger->info('Validate attestation response');

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory);
        $psr7Request = $psrHttpFactory->createRequest($request);
        try {
            $pkco = $this->attestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);
            $this->mds->verifyMeetsRequiredAuthenticatorStatus($pkco);
        } catch (Exception $e) {
            $logger->warning(sprintf('Invalid attestation "%s"', $e->getMessage()));
            return ValidationJsonResponse::invalid($e);
        }

        $credentialSource = $this->credentialSourceRepository->create(
            $publicKeyCredential,
            $nameId
        );

        $logger->info('Saving user');

        $this->userRegistrationRepository->saveUserEntity($publicKeyCredentialCreationOptions->user);
        $this->credentialSourceRepository->saveCredentialSource($credentialSource);

        $logger->info('Register user');

        $this->registrationService->register($nameId);
        $this->store->clear();

        $logger->info('Attestation verify success, user registered');
        return ValidationJsonResponse::valid();
    }
}
