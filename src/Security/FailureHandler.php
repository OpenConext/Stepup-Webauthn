<?php

/**
 * Copyright 2026 SURFnet B.V.
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

use Surfnet\Webauthn\Exception\BackupEligibleRejectedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Throwable;
use Webauthn\Bundle\Security\Handler\FailureHandler as WebauthnFailureHandler;

final class FailureHandler implements WebauthnFailureHandler, AuthenticationFailureHandlerInterface
{
    public function onFailure(Request $request, ?Throwable $exception = null): Response
    {
        $data = [
            'status' => 'error',
            'errorMessage' => $exception === null ? 'Authentication failed' : $exception->getMessage(),
        ];

        if ($this->isBackupEligibleRejection($exception)) {
            $data['errorCode'] = 'backup_eligible_rejected';
        }

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->onFailure($request, $exception);
    }

    private function isBackupEligibleRejection(?Throwable $exception): bool
    {
        while ($exception !== null) {
            if ($exception instanceof BackupEligibleRejectedException) {
                return true;
            }
            $exception = $exception->getPrevious();
        }
        return false;
    }
}
