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
use App\Service\AttestationCertificateAcceptanceService;
use App\ValidationJsonResponse;
use App\WithContextLogger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSourceRepository;

/**
 *  @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class AttestationResponseController
{
    private $userEntityRepository;
    private $credentialSourceRepository;
    private $publicKeyCredentialLoader;
    private $attestationResponseValidator;
    private $store;
    private $registrationService;
    private $logger;
    private $attestationCertificateAcceptanceService;

    public function __construct(
        PublicKeyCredentialLoader $publicKeyCredentialLoader,
        AuthenticatorAttestationResponseValidator $attestationResponseValidator,
        PublicKeyCredentialUserEntityRepository $userEntityRepository,
        PublicKeyCredentialSourceRepository $credentialSourceRepository,
        PublicKeyCredentialCreationOptionsStore $store,
        AttestationCertificateAcceptanceService $attestationCertificateAcceptanceService,
        RegistrationService $registrationService,
        LoggerInterface $logger
    ) {
        $this->attestationResponseValidator = $attestationResponseValidator;
        $this->userEntityRepository = $userEntityRepository;
        $this->credentialSourceRepository = $credentialSourceRepository;
        $this->publicKeyCredentialLoader = $publicKeyCredentialLoader;
        $this->store = $store;
        $this->registrationService = $registrationService;
        $this->logger = $logger;
        $this->attestationCertificateAcceptanceService = $attestationCertificateAcceptanceService;
    }

    /**
     * Handles the attestation public key response.
     *
     * @Route("/verify-attestation", methods={"POST"}, name="verify-attestation", )
     *
     * @param ServerRequestInterface $psr7Request
     * @param Request $request
     * @return Response
     */
    public function __invoke(ServerRequestInterface $psr7Request, Request $request): Response
    {
        $this->logger->info('Verifying if there is a pending registration from SP');

        if (!$this->registrationService->registrationRequired()) {
            $this->logger->warning('Registration is not required');
            return ValidationJsonResponse::noRegistrationRequired();
        }

        $this->logger->info('Verify valid public key credential response');

        try {
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($request->getContent());
            $response = $publicKeyCredential->getResponse();
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new UnrecoverableErrorException();
            }
        } catch (Throwable $throwable) {
            $this->logger->warning(sprintf('Invalid public key credential response "%s"', $throwable));
            return ValidationJsonResponse::invalidPublicKeyCredentialResponse();
        }

        $this->logger->info('Verify if there is an excising public key credential creation options in session');

        try {
            $publicKeyCredentialCreationOptions = $this->store->get();
        } catch (Throwable $throwable) {
            $this->logger->warning('No pending public key credential creation options in session');
            return ValidationJsonResponse::noPendingCredentialCreationOptions();
        }

        $nameId = $publicKeyCredentialCreationOptions->getUser()->getId();
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        $logger->info('Validate attestation response');

        try {
            $this->attestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);
        } catch (Throwable $exception) {
            $logger->warning(sprintf('Invalid attestation "%s"', $exception));
        }

        $credentialSource = PublicKeyCredentialSource::create(
            $publicKeyCredential,
            $nameId
        );

        $logger->info('Verify if attestation certificate is supported');
        if (!$this->attestationCertificateAcceptanceService->isSupported($credentialSource)) {
            $logger->warning('Attestation certificate is not supported');
            return ValidationJsonResponse::deviceNotSupported();
        }

        $logger->info('Saving user');

        $this->userEntityRepository->saveUserEntity($publicKeyCredentialCreationOptions->getUser());

        $this->credentialSourceRepository->saveCredentialSource($credentialSource);

        $logger->info('Register user');

        $this->registrationService->register($nameId);
        $this->store->clear();

        $logger->info('Attestation verify success, user registered');
        return ValidationJsonResponse::valid();
    }
}
