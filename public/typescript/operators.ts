import axios from 'axios';
import { complement } from 'ramda';
import { from, merge, Observable, of, throwError } from 'rxjs';
import { concatMap, filter, map, mergeMap, retryWhen, shareReplay, takeWhile, tap } from 'rxjs/operators';
import {
  deSerializedPublicKeyCredentialCreationOptions,
  deSerializedPublicKeyCredentialRequestOptions,
  isPublicKeyCredentialType,
  isWebAuthnSupported,
  serializePublicKeyCredential,
} from './functions';
import { verifyPublicKeyCredentials } from './http';
import {
  ApplicationEvent,
  SerializedPublicKeyCredential,
  SerializedPublicKeyCredentialCreationOptions,
  SerializedPublicKeyCredentialRequestOptions,
} from './models';

export type FireApplicationEvent = (event: ApplicationEvent) => (value: unknown) => void;

export const requestUserAttestation = (fe: FireApplicationEvent) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    tap<PublicKeyCredentialCreationOptions>(fe(ApplicationEvent.REQUEST_USER_FOR_ATTESTATION)),
    concatMap(() => from(navigator.credentials.create({ publicKey }))),
    tap<CredentialType | null>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS)),
  ));

export const requestUserAssertion = (fe: FireApplicationEvent) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    tap(fe(ApplicationEvent.REQUEST_USER_FOR_ASSERTION)),
    concatMap(() => from(navigator.credentials.get({ publicKey }))),
    tap<CredentialType | null>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS)),
  ));

export const filterPublicKeyCredentialType = filter(isPublicKeyCredentialType);

export const excludePublicKeyCredentialType = filter(complement(isPublicKeyCredentialType));

export const fromSerializedPublicKeyCredentialRequestOptions = (fe: FireApplicationEvent, options: SerializedPublicKeyCredentialRequestOptions) =>
  of(options).pipe(
    tap<SerializedPublicKeyCredentialRequestOptions>(fe(ApplicationEvent.DESERIALIZE_ASSERTION_RESPONSE_OPTIONS)),
    map(deSerializedPublicKeyCredentialRequestOptions),
    tap(fe(ApplicationEvent.ASSERTION_RESPONSE_OPTIONS_DE_SERIALIZED)),
    shareReplay(),
  );

export const fromSerializedPublicKeyCredentialCreationOptions = (fe: FireApplicationEvent, options: SerializedPublicKeyCredentialCreationOptions) =>
  of(options).pipe(
    tap<SerializedPublicKeyCredentialCreationOptions>(fe(ApplicationEvent.DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS)),
    map(deSerializedPublicKeyCredentialCreationOptions),
    tap(fe(ApplicationEvent.ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED)),
    shareReplay(),
  );

export const concatIfElse = <T, R1, R2>(
  condition: (value: T) => boolean,
  whenTrue: (obs: Observable<T>) => Observable<R1>,
  whenFalse: (obs: Observable<T>) => Observable<R2>,
) => concatMap((value: T) => condition(value) ? whenTrue(of(value)) : whenFalse(of(value)));

export const sendPublicKeyCredentialsToServer = (fe: FireApplicationEvent) => (s: Observable<CredentialType | null>): Observable<unknown> => s.pipe(
  filter(isPublicKeyCredentialType),
  tap<PublicKeyCredential>(fe(ApplicationEvent.SERIALIZE_PUBLIC_KEY_CREDENTIALS)),
  map(serializePublicKeyCredential),
  tap<SerializedPublicKeyCredential>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED)),
  tap<SerializedPublicKeyCredential>(fe(ApplicationEvent.SENDING_PUBLIC_KEY_CREDENTIALS)),
  concatMap(verifyPublicKeyCredentials(axios)),
  tap(fe(ApplicationEvent.RECEIVED_SERVER_RESPONSE)),
);

export let handleUnsupportedCredentialTypes = (fe: FireApplicationEvent) => (s: Observable<CredentialType | null>): Observable<never> => s.pipe(
  tap(fe(ApplicationEvent.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS)),
  () => throwError(ApplicationEvent.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
);

export const retryWhenClicked = (fe: FireApplicationEvent, clicked: Observable<unknown>) =>
  retryWhen((errors) => merge(errors.pipe(
    tap(fe(ApplicationEvent.ERROR)),
    mergeMap(() => clicked),
  )));

export const whenWebAuthnSupported = (fe: FireApplicationEvent) => takeWhile<any>(() => {
  if (!isWebAuthnSupported()) {
    fe(ApplicationEvent.NOT_SUPPORTED)(null);
    return false;
  }
  return true;
});

export const reload = () => tap(() => {
  window.location.reload();
});
