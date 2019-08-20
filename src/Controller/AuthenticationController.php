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

use App\Exception\AttestationCertificateNotSupportedException;
use App\Exception\NoActiveAuthenrequestException;
use App\Exception\UserNotFoundException;
use App\PublicKeyCredentialRequestOptionsStore;
use App\Repository\PublicKeyCredentialSourceRepository;
use App\Repository\UserRepository;
use App\Service\AttestationCertificateAcceptanceService;
use App\WithContextLogger;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthenticationController extends AbstractController
{
    private $authenticationService;
    private $userRepository;
    private $publicKeyCredentialRequestOptionsFactory;
    private $requestOptionsStore;
    private $logger;
    private $publicKeyCredentialSourceRepository;
    private $store;
    private $attestationCertificateAcceptanceService;

    public function __construct(
        AuthenticationService $authenticationService,
        UserRepository $userRepository,
        LoggerInterface $logger,
        PublicKeyCredentialRequestOptionsFactory $publicKeyCredentialCreationOptionsFactory,
        PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository,
        PublicKeyCredentialRequestOptionsStore $creationOptionsStore,
        PublicKeyCredentialRequestOptionsStore $store,
        AttestationCertificateAcceptanceService $attestationCertificateAcceptanceService
    ) {
        $this->authenticationService = $authenticationService;
        $this->userRepository = $userRepository;
        $this->publicKeyCredentialRequestOptionsFactory = $publicKeyCredentialCreationOptionsFactory;
        $this->requestOptionsStore = $creationOptionsStore;
        $this->logger = $logger;
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;
        $this->store = $store;
        $this->attestationCertificateAcceptanceService = $attestationCertificateAcceptanceService;
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see AuthenticationService for a more clean example.
     *
     * @Route("/authentication", name="app_identity_authentication")
     */
    public function __invoke(Request $request)
    {
        $this->logger->info('Verifying if there is a pending authentication request from SP');

        if (!$this->authenticationService->authenticationRequired()) {
            $this->logger->error('There is no pending authentication request from SP');
            throw new NoActiveAuthenrequestException();
        }

        $nameId = $this->authenticationService->getNameId();
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        $logger->info('Verifying if authentication is finalized');

        if ($this->authenticationService->isAuthenticated()) {
            $logger->info('Authentication is finalized returning to service provider');
            return $this->authenticationService->replyToServiceProvider();
        }

        try {
            $user = $this->userRepository->getByUserId($nameId);
        } catch (Throwable $exception) {
            $logger->error(sprintf(
                'User with nameId "%s" not found, error "%s"',
                $nameId,
                $exception->getMessage()
            ));
            throw new UserNotFoundException();
        }

        $this->logger->info('Registration is not finalized create public key credential creation options');

        $allowedCredentials = $this->publicKeyCredentialSourceRepository->allForUser($user);

        if (count($allowedCredentials) !== 1) {
            $logger->error('One credential source allowed');
            throw new UnrecoverableErrorException('One credential source allowed');
        }

        $logger->info('Verify if attestation certificate is supported');
        if (!$this->attestationCertificateAcceptanceService->isSupported($allowedCredentials[0])) {
            $logger->warning('Attestation certificate is no longer supported');
            throw new AttestationCertificateNotSupportedException();
        }

        $publicKeyCredentialRequestOptions = $this->publicKeyCredentialRequestOptionsFactory->create(
            'default',
            $allowedCredentials
        );

        $this->store->set($publicKeyCredentialRequestOptions);

        $logger->info('Return authentication page with public key credential request options');

        return $this->render(
            'default/authentication.html.twig',
            ['publicKeyOptions' => $publicKeyCredentialRequestOptions]
        );
    }
}
