import axios from 'axios';
import { merge, of, throwError } from 'rxjs';
import { concatMap, map, mergeMap, retryWhen, shareReplay } from 'rxjs/operators';
import {
  deSerializedPublicKeyCredentialRequestOptions,
  isPublicKeyCredentialType,
  isWebAuthnSupported,
  serializePublicKeyCredential,
} from './functions';
import { reload, retryClicked, showWebAuthnNotSupportedStatus } from './gui';
import { updateState } from './gui/authentication';
import { verifyPublicKeyCredentials, whenResponseIsOk } from './http';
import { AuthenticationState as S, SerializedPublicKeyCredentialRequestOptions } from './models';
import { requestUserAssertion } from './operators';

/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialRequestOptions;

(() => {
  if (!isWebAuthnSupported()) {
    showWebAuthnNotSupportedStatus();
    return;
  }
  of(publicKeyOptions).pipe(
    updateState(S.DESERIALIZE_ASSERTION_RESPONSE_OPTIONS),
    map(deSerializedPublicKeyCredentialRequestOptions),
    updateState(S.ASSERTION_RESPONSE_OPTIONS_DE_SERIALIZED),
    shareReplay(),
    updateState(S.REQUEST_USER_FOR_ASSERTION),
    requestUserAssertion,
    updateState(S.PUBLIC_KEY_CREDENTIALS),
    concatMap((credentials) => isPublicKeyCredentialType(credentials) ?
      of(credentials).pipe(
        updateState(S.SERIALIZE_PUBLIC_KEY_CREDENTIALS),
        map(serializePublicKeyCredential),
        updateState(S.PUBLIC_KEY_CREDENTIALS_SERIALIZED),
        updateState(S.SENDING_PUBLIC_KEY_CREDENTIALS),
        concatMap(verifyPublicKeyCredentials(axios)),
        updateState(S.RECEIVED_SERVER_AUTHENTICATION_RESPONSE),
        whenResponseIsOk(),
        reload(),
      )
      :
      of(credentials).pipe(
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
