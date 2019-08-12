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

namespace App\Controller;

use App\Exception\NoRegistrationRequiredException;
use App\PublicKeyCredentialCreationOptionsStore;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;

final class RegistrationController extends AbstractController
{
    private $authenticationService;
    private $registrationService;
    private $userRepository;
    private $publicKeyCredentialCreationOptionsFactory;
    private $creationOptionsStore;
    private $logger;

    public function __construct(
        AuthenticationService $authenticationService,
        RegistrationService $registrationService,
        UserRepository $userRepository,
        PublicKeyCredentialCreationOptionsFactory $publicKeyCredentialCreationOptionsFactory,
        PublicKeyCredentialCreationOptionsStore $creationOptionsStore,
        LoggerInterface $logger
    ) {
        $this->authenticationService = $authenticationService;
        $this->registrationService = $registrationService;
        $this->userRepository = $userRepository;
        $this->publicKeyCredentialCreationOptionsFactory = $publicKeyCredentialCreationOptionsFactory;
        $this->creationOptionsStore = $creationOptionsStore;
        $this->logger = $logger;
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see RegistrationService for a more clean example.
     *
     * @Route("/registration", name="app_identity_registration")
     */
    public function registrationAction()
    {
        $this->logger->info('Verifying if there is a pending registration from SP');

        if (!$this->registrationService->registrationRequired()) {
            $this->logger->warning('Registration is not required');
            throw new NoRegistrationRequiredException();
        }

        $this->logger->info('There is a pending registration');

        $this->logger->info('Verifying if registration is finalized');

        if ($this->registrationService->isRegistered()) {
            $this->logger->info('Registration is finalized returning to service provider');
            return $this->registrationService->replyToServiceProvider();
        }

        $this->logger->info('Registration is not finalized create public key credential creation options');

        $userEntity = $this->userRepository->createUserEntity('', '', null);
        $publicKeyCredentialCreationOptions = $this->publicKeyCredentialCreationOptionsFactory->create(
            'default',
            $userEntity
        );
        $this->creationOptionsStore->set($publicKeyCredentialCreationOptions);

        $this->logger->info('Return registration page for user attestation');

        return $this->render(
            'default\registration.html.twig',
            ['publicKeyOptions' => $publicKeyCredentialCreationOptions]
        );
    }
}
