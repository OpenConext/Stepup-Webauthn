import { complement } from 'ramda';
import { from, Observable, of, throwError } from 'rxjs';
import { concatMap, filter, map, shareReplay, takeWhile, tap } from 'rxjs/operators';
import { isPublicKeyCredentialType, isWebAuthnSupported } from '../function';
import {
  ApplicationEvent,
  FireApplicationEvent,
  SerializedPublicKeyCredentialCreationOptions,
  SerializedPublicKeyCredentialRequestOptions,
} from '../model';
import {
  deSerializedPublicKeyCredentialCreationOptions,
  deSerializedPublicKeyCredentialRequestOptions,
} from '../serialisation';

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

export const requestUserAttestation = (fe: FireApplicationEvent, create: (options: CredentialCreationOptions) => Promise<CredentialType | null>) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    tap<PublicKeyCredentialCreationOptions>(fe(ApplicationEvent.REQUEST_USER_FOR_ATTESTATION)),
    concatMap(() => from(create({ publicKey }))),
    tap<CredentialType | null>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS)),
  ));

export const requestUserAssertion = (fe: FireApplicationEvent, get: (options?: CredentialRequestOptions) => Promise<CredentialType | null>) => concatMap((publicKey: PublicKeyCredentialCreationOptions) =>
  of(publicKey).pipe(
    tap(fe(ApplicationEvent.REQUEST_USER_FOR_ASSERTION)),
    concatMap(() => from(get({ publicKey }))),
    tap<CredentialType | null>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS)),
  ));

export let handleUnsupportedCredentialTypes = (fe: FireApplicationEvent) => (s: Observable<CredentialType | null>): Observable<never> => s.pipe(
  tap(fe(ApplicationEvent.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS)),
  () => throwError(ApplicationEvent.UNSUPPORTED_PUBLIC_KEY_CREDENTIALS),
);

export const whenWebAuthnSupported = (fe: FireApplicationEvent) => takeWhile<any>(() => {
  if (!isWebAuthnSupported()) {
    fe(ApplicationEvent.NOT_SUPPORTED)(null);
    return false;
  }
  return true;
});
