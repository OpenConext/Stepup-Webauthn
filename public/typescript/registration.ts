import axios from 'axios';
import { merge, of, throwError } from 'rxjs';
import { concatMap, map, mergeMap, retryWhen, shareReplay } from 'rxjs/operators';
import {
  deSerializedPublicKeyCredentialCreationOptions,
  isPublicKeyCredentialType,
  isWebAuthnSupported,
  serializePublicKeyCredential,
} from './functions';
import { reload, retryClicked, showWebAuthnNotSupportedStatus } from './gui';
import { updateState } from './gui/registration';
import { verifyPublicKeyCredentials, whenResponseIsOk } from './http';
import { RegistrationState as S, SerializedPublicKeyCredentialCreationOptions } from './models';
import { concatIfElse, requestUserAttestation } from './operators';

/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
(() => {
  if (!isWebAuthnSupported()) {
    showWebAuthnNotSupportedStatus();
    return;
  }
  of(publicKeyOptions).pipe(
    updateState(S.DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS),
    map(deSerializedPublicKeyCredentialCreationOptions),
    updateState(S.ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED),
    shareReplay(),
    updateState(S.REQUEST_USER_FOR_ATTESTATION),
    requestUserAttestation,
    updateState(S.PUBLIC_KEY_CREDENTIALS),
    concatIfElse(
      isPublicKeyCredentialType,
      s => s.pipe(
        updateState(S.SERIALIZE_PUBLIC_KEY_CREDENTIALS),
        map(serializePublicKeyCredential),
        updateState(S.PUBLIC_KEY_CREDENTIALS_SERIALIZED),
        updateState(S.SENDING_PUBLIC_KEY_CREDENTIALS),
        concatMap(verifyPublicKeyCredentials(axios)),
        updateState(S.RECEIVED_SERVER_RESPONSE),
        whenResponseIsOk(),
        reload(),
      ),
      s => s.pipe(
        updateState(S.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
        () => throwError(S.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
      ),
    ),
    retryWhen((errors) => merge(errors.pipe(
      updateState(S.ERROR),
      mergeMap(retryClicked),
    ))),
  ).subscribe();
})();
