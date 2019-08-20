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

use App\ValidationJsonResponse;
use App\PublicKeyCredentialRequestOptionsStore;
use App\Service\AttestationCertificateAcceptanceService;
use App\WithContextLogger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;

/**
 *  @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class AssertionResponseController
{
    private $publicKeyCredentialLoader;
    private $assertionResponseValidator;
    private $logger;
    private $authenticationService;
    private $store;

    public function __construct(
        PublicKeyCredentialLoader $publicKeyCredentialLoader,
        AuthenticatorAssertionResponseValidator $assertionResponseValidator,
        AuthenticationService $authenticationService,
        PublicKeyCredentialRequestOptionsStore $store,
        LoggerInterface $logger
    ) {
        $this->assertionResponseValidator = $assertionResponseValidator;
        $this->publicKeyCredentialLoader = $publicKeyCredentialLoader;
        $this->logger = $logger;
        $this->authenticationService = $authenticationService;
        $this->store = $store;
    }

    /**
     * Handles the assertion public key response.
     *
     * @Route("/verify-assertion", methods={"POST"}, name="verify-assertion", )
     *
     * @param ServerRequestInterface $psr7Request
     * @param Request $request
     * @return Response
     */
    public function __invoke(ServerRequestInterface $psr7Request, Request $request): Response
    {
        $this->logger->info('Verifying if there is a pending authentication from SP');

        if (!$this->authenticationService->authenticationRequired()) {
            $this->logger->warning('No authentication required');
            return ValidationJsonResponse::noAuthenticationRequired();
        }
        $nameId = $this->authenticationService->getNameId();
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        $logger->info('Verify valid public key credential response');

        try {
            $content = $request->getContent();
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($content);
            $response = $publicKeyCredential->getResponse();
            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw new UnrecoverableErrorException();
            }
        } catch (Throwable $exception) {
            $logger->warning(sprintf('Invalid public key credential response "%s"', $exception));
            return ValidationJsonResponse::invalidPublicKeyCredentialResponse();
        }

        $logger->info('Verify if there is an excising public key credential assertion options in session');
        try {
            $publicKeyCredentialRequestOptions = $this->store->get();
        } catch (Throwable $exception) {
            $logger->warning(sprintf('Invalid attestation response "%s"', $exception));
            return ValidationJsonResponse::noPendingCredentialAssertOptions();
        }

        $logger->info('Validate assertion response');

        try {
            $this->assertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $response,
                $publicKeyCredentialRequestOptions,
                $psr7Request,
                $nameId
            );
        } catch (Throwable $throwable) {
            $logger->warning(sprintf('Invalid attestation "%s"', $throwable));
            return ValidationJsonResponse::invalid();
        }

        $logger->info('Attestation success, user verified');

        $this->authenticationService->authenticate();
        $this->store->clear();

        return ValidationJsonResponse::valid();
    }
}
