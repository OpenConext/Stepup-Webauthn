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
 * Return status response for web gui. @see public/typescript/gui files.
 */
class ValidationJsonResponse extends JsonResponse
{

    public static function invalidPublicKeyCredentialResponse(Exception $e): self
    {
        return new self(['status' => 'error', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function noAuthenticationRequired(Exception $e): self
    {
        return new self(['status' => 'noAuthenticationRequired', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function deviceNotSupported(Exception $e): self
    {
        return new self(['status' => 'deviceNotSupported', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function noPendingCredentialAssertOptions(Exception $e): self
    {
        return new self(['status' => 'error', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function valid(): self
    {
        return new self(['status' => 'ok']);
    }

    public static function invalid(Exception $e): self
    {
        return new self(['status' => 'invalid', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function noRegistrationRequired(Exception $e): self
    {
        return new self(['status' => 'noRegistrationRequired', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function noPendingCredentialCreationOptions(Exception $e): self
    {
        return new self(['status' => 'error', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }

    public static function missingAttestationStatement(Exception $e): self
    {
        return new self(['status' => 'missingAttestationStatement', 'error_code' => Art::forException($e)], Response::HTTP_BAD_REQUEST);
    }
}
