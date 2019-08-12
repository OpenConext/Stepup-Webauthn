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

use App\PublicKeyCredentialCreationOptionsStore;
use App\Repository\UserRepository;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;

class AuthenticationController extends AbstractController
{
    private $authenticationService;
    private $registrationService;
    private $userRepository;
    private $publicKeyCredentialCreationOptionsFactory;
    private $creationOptionsStore;

    public function __construct(
        AuthenticationService $authenticationService,
        RegistrationService $registrationService,
        UserRepository $userRepository,
        PublicKeyCredentialCreationOptionsFactory $publicKeyCredentialCreationOptionsFactory,
        PublicKeyCredentialCreationOptionsStore $creationOptionsStore
    ) {
        $this->authenticationService = $authenticationService;
        $this->registrationService = $registrationService;
        $this->userRepository = $userRepository;
        $this->publicKeyCredentialCreationOptionsFactory = $publicKeyCredentialCreationOptionsFactory;
        $this->creationOptionsStore = $creationOptionsStore;
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see AuthenticationService for a more clean example.
     *
     * @Route("/authentication", name="app_identity_authentication")
     */
    public function authenticationAction(Request $request)
    {
        $nameId = $this->authenticationService->getNameId();

        if ($request->get('action') === 'error') {
            $this->authenticationService->reject($request->get('message'));
            return $this->authenticationService->replyToServiceProvider();
        }

        if ($request->get('action') === 'authenticate') {
            // The application should very if the user matches the nameId.
            $this->authenticationService->authenticate();
            return $this->authenticationService->replyToServiceProvider();
        }

        $requiresAuthentication = $this->authenticationService->authenticationRequired();
        $response = new Response(null, $requiresAuthentication ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('default/authentication.html.twig', [
            'requiresAuthentication' => $requiresAuthentication,
            'NameID' => $nameId ?: 'unknown',
        ], $response);
    }
}
