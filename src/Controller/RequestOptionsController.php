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
use Surfnet\Webauthn\Exception\UserNotFoundException;
use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Surfnet\Webauthn\PublicKeyCredentialRequestOptionsStore;
use Surfnet\Webauthn\Repository\UserRepository;
use Surfnet\Webauthn\ValidationJsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Webauthn\Bundle\Security\Handler\DefaultCreationOptionsHandler;
use Webauthn\Bundle\Security\Handler\DefaultRequestOptionsHandler;
use function array_key_exists;
use function json_decode;
use function sprintf;

final readonly class RequestOptionsController
{
    public function __construct(
        private PublicKeyCredentialRequestOptionsStore $requestOptionsStore,
        private UserRepository $userRepository,
        private DefaultRequestOptionsHandler $requestOptionsHandler,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handles the attestation public key request.
     */
    #[Route(path: '/request-options', name: 'request-options', methods: ['POST'])]
    public function action(Request $request): Response
    {

        $requestContent = json_decode($request->getContent(), true);
        if (!array_key_exists('username', $requestContent)) {
            throw new UserNotFoundException(
                'The user was not found in the request json content. It should be stored in the username field.'
            );
        }
        try {
            $nameId = $requestContent['username'];
            $user = $this->userRepository->getByUserId($nameId);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'User with nameId "%s" not found, error "%s"',
                $nameId,
                $exception->getMessage()
            ));
            throw new UserNotFoundException();
        }
        $publicKeyCredentialRequestOptions = $this->requestOptionsStore->get();

        try {
            $response = $this->requestOptionsHandler->onRequestOptions(
                $publicKeyCredentialRequestOptions,
                $user
            );
        } catch (RuntimeException $e) {
            $this->logger->warning(sprintf('Unable to create the authentication options: "%s"', $e->getMessage()));
            return ValidationJsonResponse::reportErrorMessage($e);
        }

        return $response;
    }
}
