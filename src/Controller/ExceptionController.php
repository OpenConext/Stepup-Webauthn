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

use Surfnet\StepupBundle\Request\RequestId;
use Surfnet\Webauthn\Exception\AttestationCertificateNotSupportedException;
use Surfnet\Webauthn\Exception\NoAuthnrequestException;
use Surfnet\Webauthn\Exception\UserNotFoundException;
use Surfnet\Webauthn\Service\ClientMetadataService;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\StepupBundle\Controller\ExceptionController as BaseExceptionController;
use Surfnet\StepupBundle\Exception\Art;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final class ExceptionController extends BaseExceptionController
{
    public function __construct(
        private readonly ClientMetadataService $clientMetadataService,
        TranslatorInterface $translator,
        RequestId $requestId
    ) {
        parent::__construct($translator, $requestId);
    }

    public function show(Request $request, Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);

        $template = 'Exception\error.html.twig';
        if ($statusCode == 404) {
            $template = 'Exception\error404.html.twig';
        }

        $response = new Response('', $statusCode);

        $errorCode = Art::forException($exception);

        return $this->render(
            $template,
            $this->clientMetadataService->generateMetadata($request) +
            ['error_code' => $errorCode] +
            $this->getPageTitleAndDescription($exception),
            $response
        );
    }

    protected function getPageTitleAndDescription(Throwable $exception): array
    {
        $translator = $this->getTranslator();

        if ($exception instanceof UserNotFoundException) {
            $title = $translator->trans('user_not_found.title');
            $description = $translator->trans('user_not_found.description');
        }
        if ($exception instanceof NoAuthnrequestException) {
            $title = $translator->trans('error_title');
            $description = $translator->trans('status.no_active_request');
        }
        if ($exception instanceof AttestationCertificateNotSupportedException) {
            $title = $translator->trans('error_title');
            $description = $translator->trans('status.authenticator_not_supported');
        }

        if (isset($title) && isset($description)) {
            return [
                'title' => $title,
                'description' => $description,
            ];
        }

        return parent::getPageTitleAndDescription($exception);
    }

    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof NoAuthnrequestException) {
            return Response::HTTP_BAD_REQUEST;
        }
        if ($exception instanceof UnrecoverableErrorException) {
            return Response::HTTP_BAD_REQUEST;
        }
        if ($exception instanceof AttestationCertificateNotSupportedException) {
            return Response::HTTP_BAD_REQUEST;
        }
        return parent::getStatusCode($exception);
    }
}
