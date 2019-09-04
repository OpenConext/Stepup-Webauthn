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

namespace Test\Controller;

use App\Controller\AttestationResponseController;
use App\Entity\PublicKeyCredentialSource;
use App\Entity\User;
use App\Exception\AttestationStatementNotFoundException;
use App\Exception\NoActiveAuthenrequestException;
use App\PublicKeyCredentialCreationOptionsStore;
use App\Repository\PublicKeyCredentialSourceRepository;
use App\Service\AttestationCertificateTrustStore;
use App\ValidationJsonResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;

class AttestationResponseControllerTest extends TestCase
{
    use WebAuthnTestTrait;

    private $userEntityRepository;
    private $credentialSourceRepository;
    private $publicKeyCredentialLoader;
    private $attestationResponseValidator;
    private $store;
    private $registrationService;
    private $logger;
    private $attestationCertificateAcceptanceService;
    private $controller;
    private $psr7Request;
    private $request;

    public function test__construct()
    {
        $this->assertInstanceOf(AttestationResponseController::class, $this->controller);
    }

    public function test__there_is_no_pending_registration_from_SP()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => false]);
        $this->assertEquals(
            ValidationJsonResponse::noRegistrationRequired(new NoActiveAuthenrequestException()),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__when_there_is_an_invalid_public_key_credential_response()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $response = Mockery::mock(AuthenticatorAssertionResponse::class);
        $this->setAuthenticatorResponse($response);
        $this->assertEquals(
            ValidationJsonResponse::invalidPublicKeyCredentialResponse(new UnrecoverableErrorException('Invalid response type')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__when_there_is_not_an_existing_public_key_credential_creation_options_in_session()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $this->setAuthenticatorResponse(Mockery::mock(AuthenticatorAttestationResponse::class));
        $this->store->shouldReceive('get')->andThrow(UnrecoverableErrorException::class, 'Some Error');
        $this->assertEquals(
            ValidationJsonResponse::noPendingCredentialCreationOptions(new UnrecoverableErrorException('Some Error')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__when_attestation_response_is_invalid()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $response = Mockery::mock(AuthenticatorAttestationResponse::class);
        $this->setAuthenticatorResponse($response);
        $options = Mockery::mock(PublicKeyCredentialCreationOptions::class);
        $options->shouldReceive([
            'getUser->getId' => 'userId123'
        ]);
        $this->store->shouldReceive('get')->andReturn($options);
        $this->attestationResponseValidator
            ->shouldReceive('check')
            ->with(
                $response,
                $options,
                $this->psr7Request
            )
            ->andThrow(\Exception::class, 'Invalid');
        $this->assertEquals(
            ValidationJsonResponse::invalid(new \Exception('Invalid')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__verify_if_attestation_certificate_is_not_supported()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $response = Mockery::mock(AuthenticatorAttestationResponse::class);
        $publicKeyCredential = $this->setAuthenticatorResponse($response);
        $options = Mockery::mock(PublicKeyCredentialCreationOptions::class);
        $options->shouldReceive([
            'getUser->getId' => 'userId123'
        ]);
        $this->store->shouldReceive('get')->andReturn($options);
        $this->attestationResponseValidator
            ->shouldReceive('check')
            ->with(
                $response,
                $options,
                $this->psr7Request
            );
        $credentialSource = Mockery::mock(PublicKeyCredentialSource::class);
        $this->credentialSourceRepository
            ->shouldReceive('create')
            ->with(
                $publicKeyCredential,
                'userId123'
            )
            ->andReturn($credentialSource);
        $this->attestationCertificateAcceptanceService
            ->shouldReceive('validate')
            ->with($credentialSource)
            ->andThrow(\Exception::class, 'Not supported');
        $this->assertEquals(
            ValidationJsonResponse::deviceNotSupported(new \Exception('Not supported')),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__verify_if_attestation_certificate_is_not_found()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $response = Mockery::mock(AuthenticatorAttestationResponse::class);
        $publicKeyCredential = $this->setAuthenticatorResponse($response);
        $options = Mockery::mock(PublicKeyCredentialCreationOptions::class);
        $options->shouldReceive([
            'getUser->getId' => 'userId123'
        ]);
        $this->store->shouldReceive('get')->andReturn($options);
        $this->attestationResponseValidator
            ->shouldReceive('check')
            ->with(
                $response,
                $options,
                $this->psr7Request
            );
        $credentialSource = Mockery::mock(PublicKeyCredentialSource::class);
        $this->credentialSourceRepository
            ->shouldReceive('create')
            ->with(
                $publicKeyCredential,
                'userId123'
            )
            ->andReturn($credentialSource);
        $this->attestationCertificateAcceptanceService
            ->shouldReceive('validate')
            ->with($credentialSource)
            ->andThrow(AttestationStatementNotFoundException::class);
        $this->assertEquals(
            ValidationJsonResponse::missingAttestationStatement(new AttestationStatementNotFoundException()),
            $this->controller->action($this->psr7Request, $this->request)
        );
        $this->assertLogs();
    }

    public function test__verify_if_attestation_is_valid()
    {
        $this->registrationService->shouldReceive(['registrationRequired' => true]);
        $response = Mockery::mock(AuthenticatorAttestationResponse::class);
        $publicKeyCredential = $this->setAuthenticatorResponse($response);
        $options = Mockery::mock(PublicKeyCredentialCreationOptions::class);
        $user = Mockery::mock(User::class);
        $user->shouldReceive(['getId' => 'userId123']);
        $options->shouldReceive(['getUser' => $user]);
        $this->store->shouldReceive('get')->andReturn($options);
        $this->attestationResponseValidator
            ->shouldReceive('check')
            ->with(
                $response,
                $options,
                $this->psr7Request
            );
        $credentialSource = Mockery::mock(PublicKeyCredentialSource::class);
        $this->credentialSourceRepository
            ->shouldReceive('create')
            ->with(
                $publicKeyCredential,
                'userId123'
            )
            ->andReturn($credentialSource);
        $this->attestationCertificateAcceptanceService
            ->shouldReceive('validate')
            ->with($credentialSource);

        $this->userEntityRepository->shouldReceive('saveUserEntity');
        $this->credentialSourceRepository->shouldReceive('saveCredentialSource');
        $this->registrationService->shouldReceive('register');
        $this->store->shouldReceive('clear');

        $this->assertEquals(
            ValidationJsonResponse::valid(),
            $this->controller->action($this->psr7Request, $this->request)
        );

        $this->userEntityRepository->shouldHaveReceived('saveUserEntity', [$user]);
        $this->credentialSourceRepository->shouldHaveReceived('saveCredentialSource', [$credentialSource]);
        $this->registrationService->shouldHaveReceived('register', ['userId123']);
        $this->store->shouldHaveReceived('clear');

        $this->assertLogs();
    }

    protected function setUp(): void
    {
        $this->attestationResponseValidator = Mockery::mock(AuthenticatorAttestationResponseValidator::class);
        $this->userEntityRepository = Mockery::mock(PublicKeyCredentialUserEntityRepository::class);
        $this->credentialSourceRepository = Mockery::mock(PublicKeyCredentialSourceRepository::class);
        $this->store = Mockery::mock(PublicKeyCredentialCreationOptionsStore::class);
        $this->attestationCertificateAcceptanceService = Mockery::mock(AttestationCertificateTrustStore::class);
        $this->registrationService = Mockery::mock(RegistrationService::class);
        $this->logger = new BufferingLogger();
        $this->publicKeyCredentialLoader = Mockery::mock(PublicKeyCredentialLoader::class);
        $this->controller = new AttestationResponseController(
            $this->publicKeyCredentialLoader,
            $this->attestationResponseValidator,
            $this->userEntityRepository,
            $this->credentialSourceRepository,
            $this->store,
            $this->attestationCertificateAcceptanceService,
            $this->registrationService,
            $this->logger
        );
        $this->psr7Request = Mockery::mock(ServerRequestInterface::class);
        $this->request = Mockery::mock(Request::class);
    }
}
