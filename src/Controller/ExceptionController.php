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
use App\Exception\NoAuthnrequestException;
use App\Exception\UserNotFoundException;
use DateTime;
use Exception;
use Surfnet\GsspBundle\Exception\UnrecoverableErrorException;
use Surfnet\StepupBundle\Controller\ExceptionController as BaseExceptionController;
use Surfnet\StepupBundle\Exception\Art;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ExceptionController extends BaseExceptionController
{
    public function showAction(Request $request, Exception $exception)
    {
        $statusCode = $this->getStatusCode($exception);

        $template = 'Exception\error.html.twig';
        if ($statusCode == 404) {
            $template = 'Exception\error404.html.twig';
        }

        $response = new Response('', $statusCode);

        $timestamp = (new DateTime)->format(DateTime::ISO8601);
        $hostname = $request->getHost();
        $requestId = $this->get('surfnet_stepup.request.request_id');
        $errorCode = Art::forException($exception);
        $userAgent = $request->headers->get('User-Agent');
        $ipAddress = $request->getClientIp();

        return $this->render(
            $template,
            [
                'timestamp' => $timestamp,
                'hostname' => $hostname,
                'request_id' => $requestId->get(),
                'error_code' => $errorCode,
                'user_agent' => $userAgent,
                'ip_address' => $ipAddress,
            ] + $this->getPageTitleAndDescription($exception),
            $response
        );
    }

    /**
     * @param Exception $exception
     * @return array View parameters 'title' and 'description'
     */
    protected function getPageTitleAndDescription(Exception $exception)
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

    /**
     * @param Exception $exception
     * @return int HTTP status code
     */
    protected function getStatusCode(
        Exception $exception
    ) {
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
