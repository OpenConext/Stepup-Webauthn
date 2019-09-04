import axios from 'axios';
import { complement } from 'ramda';
import { from, merge, Observable, of, throwError } from 'rxjs';
import { concatMap, filter, map, mergeMap, retryWhen, shareReplay, takeWhile } from 'rxjs/operators';
import {
  deSerializedPublicKeyCredentialCreationOptions,
  deSerializedPublicKeyCredentialRequestOptions,
  isPublicKeyCredentialType,
  isWebAuthnSupported,
  serializePublicKeyCredential,
} from './functions';
import { retryClicked } from './gui';
import { verifyPublicKeyCredentials } from './http';
import {
  ApplicationEvent as S,
  SerializedPublicKeyCredentialCreationOptions,
  SerializedPublicKeyCredentialRequestOptions,
} from './models';

export type FireApplicationEvent = (event: S) => <T>(source: T) => T;

export const requestUserAttestation = (fe: FireApplicationEvent) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    fe(S.REQUEST_USER_FOR_ATTESTATION),
    concatMap(() => from(navigator.credentials.create({ publicKey }))),
    fe(S.PUBLIC_KEY_CREDENTIALS),
  ));

export const requestUserAssertion = (fe: FireApplicationEvent) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    fe(S.REQUEST_USER_FOR_ASSERTION),
    concatMap(() => from(navigator.credentials.get({ publicKey }))),
    fe(S.PUBLIC_KEY_CREDENTIALS),
  ));

export const filterPublicKeyCredentialType = filter(isPublicKeyCredentialType);

export const excludePublicKeyCredentialType = filter(complement(isPublicKeyCredentialType));

export const fromSerializedPublicKeyCredentialRequestOptions = (fe: FireApplicationEvent, options: SerializedPublicKeyCredentialRequestOptions) =>
  of(options).pipe(
    fe(S.DESERIALIZE_ASSERTION_RESPONSE_OPTIONS),
    map(deSerializedPublicKeyCredentialRequestOptions),
    fe(S.ASSERTION_RESPONSE_OPTIONS_DE_SERIALIZED),
    shareReplay(),
  );

export const fromSerializedPublicKeyCredentialCreationOptions = (fe: FireApplicationEvent, options: SerializedPublicKeyCredentialCreationOptions) =>
  of(options).pipe(
    fe(S.DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS),
    map(deSerializedPublicKeyCredentialCreationOptions),
    fe(S.ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED),
    shareReplay(),
  );

export const concatIfElse = <T, R1, R2>(
  condition: (value: T) => boolean,
  whenTrue: (obs: Observable<T>) => Observable<R1>,
  whenFalse: (obs: Observable<T>) => Observable<R2>,
) => concatMap((value: T) => condition(value) ? whenTrue(of(value)) : whenFalse(of(value)));

export const sendPublicKeyCredentialsToServer = (fe: FireApplicationEvent) => (s: Observable<CredentialType | null>): Observable<unknown> => s.pipe(
  filter(isPublicKeyCredentialType),
  fe(S.SERIALIZE_PUBLIC_KEY_CREDENTIALS),
  map(serializePublicKeyCredential),
  fe(S.PUBLIC_KEY_CREDENTIALS_SERIALIZED),
  fe(S.SENDING_PUBLIC_KEY_CREDENTIALS),
  concatMap(verifyPublicKeyCredentials(axios)),
  fe(S.RECEIVED_SERVER_RESPONSE),
);

export let handleUnsupportedCredentialTypes = (fe: FireApplicationEvent) => (s: Observable<CredentialType | null>): Observable<never> => s.pipe(
  fe(S.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
  () => throwError(S.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
);

export const retryWhenClicked = (fe: FireApplicationEvent) =>
  retryWhen((errors) => merge(errors.pipe(
    fe(S.ERROR),
    mergeMap(retryClicked),
  )));

export const whenWebAuthnSupported = (fe: FireApplicationEvent) => takeWhile<any>(() => {
  if (!isWebAuthnSupported()) {
    of(1).pipe(fe(S.NOT_SUPPORTED)).subscribe();
    return false;
  }
  return true;
});
