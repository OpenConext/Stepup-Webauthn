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

namespace Surfnet\Webauthn;

use Exception;
use Surfnet\StepupBundle\Exception\Art;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Return status response for Stimulus app
 */
class ValidationJsonResponse extends JsonResponse
{
    public static function noAuthenticationRequired(Exception $e): self
    {
        return new self(['status' => 'noAuthenticationRequired', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function deviceNotSupported(Exception $e): self
    {
        return new self(['status' => 'deviceNotSupported', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function reportErrorMessage(Exception $e): self
    {
        return new self(['status' => 'error', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function valid(): self
    {
        return new self(['status' => 'ok', 'errorMessage' => '']);
    }

    public static function invalid(Exception $e): self
    {
        return new self(['status' => 'invalid', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function noRegistrationRequired(Exception $e): self
    {
        return new self(['status' => 'noRegistrationRequired', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function missingAttestationStatement(Exception $e): self
    {
        return new self(['status' => 'missingAttestationStatement', 'errorMessage' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }
}
