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
use Surfnet\Webauthn\PublicKeyCredentialRequestOptionsStore;
use Surfnet\Webauthn\ValidationJsonResponse;
use Surfnet\Webauthn\WithContextLogger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class AssertionResponseController
{
    public function __construct(
        private PublicKeyCredentialLoader $publicKeyCredentialLoader,
        private AuthenticatorAssertionResponseValidator $assertionResponseValidator,
        private AuthenticationService $authenticationService,
        private PublicKeyCredentialRequestOptionsStore $store,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handles the assertion public key response.
     */
    #[Route(path: '/verify-assertion', name: 'verify-assertion', methods: ['POST'])]
    public function action(ServerRequestInterface $psr7Request, Request $request): Response
    {
        $this->logger->info('Verifying if there is a pending authentication from SP');

        if (!$this->authenticationService->authenticationRequired()) {
            $this->logger->warning('No authentication required');
            return ValidationJsonResponse::noAuthenticationRequired(new NoActiveAuthenrequestException());
        }
        $nameId = $this->authenticationService->getNameId();
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        $logger->info('Verify valid public key credential response');

        try {
            $content = $request->getContent();
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($content);
            $response = $publicKeyCredential->getResponse();
            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw new UnrecoverableErrorException('Invalid response type');
            }
        } catch (Exception $exception) {
            $logger->warning(sprintf('Invalid public key credential response "%s"', $exception->getMessage()));
            return ValidationJsonResponse::invalidPublicKeyCredentialResponse($exception);
        }

        $logger->info('Verify if there is an existing public key credential assertion options in session');
        try {
            $publicKeyCredentialRequestOptions = $this->store->get();
        } catch (Exception $exception) {
            $logger->warning(sprintf('Invalid attestation response "%s"', $exception->getMessage()));
            return ValidationJsonResponse::noPendingCredentialAssertOptions($exception);
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
        } catch (Exception $throwable) {
            $logger->warning(sprintf('Invalid attestation "%s"', $throwable->getMessage()));
            return ValidationJsonResponse::invalid($throwable);
        }

        $logger->info('Attestation success, user verified');

        $this->authenticationService->authenticate();
        $this->store->clear();

        return ValidationJsonResponse::valid();
    }
}
