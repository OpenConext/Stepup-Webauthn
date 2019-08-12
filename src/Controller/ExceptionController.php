<?php

/**
 * Copyright 2018 SURFnet B.V.
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

        if ($statusCode == 404) {
            $template = 'Exception\error404.html.twig';
        } else {
            $template = 'Exception\error.html.twig';
        }

        $response = new Response('', $statusCode);

        $timestamp = (new DateTime)->format(DateTime::ISO8601);
        $hostname  = $request->getHost();
        $requestId = $this->get('surfnet_stepup.request.request_id');
        $errorCode = Art::forException($exception);
        $userAgent = $request->headers->get('User-Agent');
        $ipAddress = $request->getClientIp();

        return $this->render(
            $template,
            [
                'timestamp'   => $timestamp,
                'hostname'    => $hostname,
                'request_id'  => $requestId->get(),
                'error_code'  => $errorCode,
                'user_agent'  => $userAgent,
                'ip_address'  => $ipAddress,
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

        if ($exception instanceof NoRegistrationRequiredException) {
            $title = $translator->trans('registration.not_required.title');
            $description = $translator->trans('registration.not_required.description');
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
    protected function getStatusCode(Exception $exception)
    {
        if ($exception instanceof NoRegistrationRequiredException) {
            return Response::HTTP_BAD_REQUEST;
        }
        if ($exception instanceof UnrecoverableErrorException) {
            return Response::HTTP_BAD_REQUEST;
        }
        return parent::getStatusCode($exception);
    }
}
