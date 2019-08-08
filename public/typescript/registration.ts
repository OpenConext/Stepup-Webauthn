import axios from 'axios';
import { merge, of, throwError } from 'rxjs';
import { concatMap, map, mergeMap, retryWhen, shareReplay } from 'rxjs/operators';
import {
  deSerializedPublicKeyCredentialCreationOptions,
  isPublicKeyCredentialType,
  serializePublicKeyCredential,
} from './functions';
import { retryClicked, submitForm, updateState } from './gui';
import { sendPublicKeyCredentials } from './http';
import { RegistrationState as S, SerializedPublicKeyCredentialCreationOptions } from './models';
import { requestUserAttestation } from './operators';

/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;

of(publicKeyOptions).pipe(
  updateState(S.DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS),
  map(deSerializedPublicKeyCredentialCreationOptions),
  updateState(S.ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED),
  shareReplay(),
  updateState(S.REQUEST_USER_FOR_ATTESTATION),
  requestUserAttestation,
  updateState(S.PUBLIC_KEY_CREDENTIALS),
  concatMap((credentials) => isPublicKeyCredentialType(credentials) ?
    of(credentials).pipe(
      updateState(S.SERIALIZE_PUBLIC_KEY_CREDENTIALS),
      map(serializePublicKeyCredential),
      updateState(S.PUBLIC_KEY_CREDENTIALS_SERIALIZED),
      updateState(S.SENDING_PUBLIC_KEY_CREDENTIALS),
      concatMap(sendPublicKeyCredentials(axios)),
      updateState(S.RECEIVED_SERVER_RESPONSE),
      submitForm(),
    )
    :
    of(credentials).pipe(
      updateState(S.UNSUPORTED_PUBLIC_KEY_CREDENTIALS),
      () => throwError(S.UNSUPORTED_PUBLIC_KEY_CREDENTIALS),
    ),
  ),
  retryWhen((errors) =>
    merge(
      errors.pipe(
        updateState(S.ERROR),
        mergeMap(retryClicked),
      ),
    )),
).subscribe();
