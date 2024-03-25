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

use Surfnet\Webauthn\Exception\NoAuthnrequestException;
use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Surfnet\Webauthn\Repository\UserRepository;
use Surfnet\Webauthn\Service\ClientMetadataService;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Symfony\Component\HttpFoundation\Request;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly UserRepository $userRepository,
        private readonly PublicKeyCredentialCreationOptionsFactory $publicKeyCredentialCreationOptionsFactory,
        private readonly PublicKeyCredentialCreationOptionsStore $creationOptionsStore,
        private readonly LoggerInterface $logger,
        private readonly ClientMetadataService $clientMetadataService,
        private readonly string $userDisplayName
    ) {
    }

    #[Route(path: '/registration', name: 'app_identity_registration', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $this->logger->info('Verifying if there is a pending registration from SP');

        if (!$this->registrationService->registrationRequired()) {
            $this->logger->warning('Registration is not required');
            throw new NoAuthnrequestException();
        }

        $this->logger->info('There is a pending registration');

        $this->logger->info('Verifying if registration is finalized');

        if ($this->registrationService->isRegistered()) {
            $this->logger->info('Registration is finalized returning to service provider');
            return $this->registrationService->replyToServiceProvider();
        }

        $this->logger->info('Registration is not finalized. Create public key credential creation options');

        $userEntity = $this->userRepository->createUser($this->userDisplayName);
        $publicKeyCredentialCreationOptions = $this->publicKeyCredentialCreationOptionsFactory->create(
            'default',
            $userEntity
        );
        $this->creationOptionsStore->set($publicKeyCredentialCreationOptions);

        $this->logger->info('Return registration page for user attestation');

        return $this->render(
            'default\registration.html.twig',
            [
                'userEntity' => $userEntity
            ] + $this->clientMetadataService->generateMetadata($request)
        );
    }
}
