<?php

/**
 * Copyright 2025 SURFnet B.V.
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

namespace Surfnet\Webauthn\Security;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Surfnet\Webauthn\PublicKeyCredentialCreationOptionsStore;
use Surfnet\Webauthn\WithContextLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
final readonly class AuthenticationListener
{
    public function __construct(
        private PublicKeyCredentialCreationOptionsStore $store,
        private AuthenticationService $authenticationService,
        private RegistrationService $registrationService,
        private LoggerInterface $logger
    ) {
    }
    public function __invoke(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        $nameId = $event->getUser()->id;
        $logger = WithContextLogger::from($this->logger, ['nameId' => $nameId]);

        if ($routeName == 'webauthn.controller.security.main.creation.result') {
            $logger->info('Attestation verify success, user registered');

            $this->registrationService->register($nameId);
            $this->store->clear();
            return;
        }

        if ($routeName == 'webauthn.controller.security.main.request.result') {
            $logger->info('Assertion success, user verified');

            $this->authenticationService->authenticate();
            $this->store->clear();
            return;
        }

        throw new RuntimeException("Authentication listener encountered unexpected route");
    }
}
