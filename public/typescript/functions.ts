import {
  always,
  anyPass,
  complement,
  cond,
  isEmpty,
  isNil,
  lensPath,
  map,
  over,
  pickBy,
  propSatisfies,
  reduce,
  splitEvery,
  replace,
  T as TRUE,
  unless,
} from 'ramda';
import { decode, encode } from 'urlsafe-base64';
import {
  SerializedAuthenticatorAssertionResponse,
  SerializedAuthenticatorAttestationResponse,
  SerializedAuthenticatorResponse,
  SerializedPublicKeyCredential,
  SerializedPublicKeyCredentialCreationOptions,
  SerializedPublicKeyCredentialRequestOptions,
} from './models';

export const Base64UrlSafeToUInt8 = (base64: string): BufferSource => Uint8Array.from(decode(base64)).buffer;
export const UInt8ToBase64UrlSafe = (buffer: BufferSource): string => encode(Buffer.from(buffer as any));

export const idLens = lensPath(['id']);
export const Base64UrlSafeToUInt8Id: <T extends { id: string }>(entity: T) => Omit<T, 'id'> & { id: BufferSource } = over(idLens, Base64UrlSafeToUInt8) as any;
export const UInt8ToBase64UrlSafeId: <T extends { id: BufferSource }>(entity: T) => Omit<T, 'id'> & { id: string } = over(idLens, UInt8ToBase64UrlSafe) as any;
export const Base64UrlSafeToUInt8Ids = map(Base64UrlSafeToUInt8Id);
export const optionalBase64UrlSafeToUInt8Ids = unless(isNil, Base64UrlSafeToUInt8Ids);
export const removeEmptyAndUndefined = pickBy(complement(anyPass([isNil, isEmpty])));

export const deSerializedPublicKeyCredentialCreationOptions: (options: SerializedPublicKeyCredentialCreationOptions) => PublicKeyCredentialCreationOptions =
  ({ rp, user, challenge, extensions, attestation, authenticatorSelection, timeout, pubKeyCredParams, excludeCredentials }) =>
    removeEmptyAndUndefined(({
      rp,
      user: Base64UrlSafeToUInt8Id(user),
      challenge: Base64UrlSafeToUInt8(challenge),
      pubKeyCredParams,
      timeout,
      excludeCredentials: optionalBase64UrlSafeToUInt8Ids(excludeCredentials),
      authenticatorSelection,
      attestation,
      extensions,
    }));

export const deSerializedPublicKeyCredentialRequestOptions: (options: SerializedPublicKeyCredentialRequestOptions) => PublicKeyCredentialRequestOptions =
  ({ challenge, extensions, timeout, allowCredentials, rpId, userVerification }) =>
    removeEmptyAndUndefined(({
      rpId,
      challenge: Base64UrlSafeToUInt8(challenge),
      timeout,
      allowCredentials: optionalBase64UrlSafeToUInt8Ids(allowCredentials),
      userVerification,
      extensions,
    }));

export const isAuthenticatorAttestationResponse: (response: AuthenticatorResponse) => response is AuthenticatorAttestationResponse = propSatisfies(complement(isNil), 'attestationObject') as any;
export const isAuthenticatorAssertionResponse: (response: AuthenticatorResponse) => response is AuthenticatorAssertionResponse = propSatisfies(complement(isNil), 'signature') as any;

export const serializeAuthenticatorAttestationResponse: (response: AuthenticatorAttestationResponse) => SerializedAuthenticatorAttestationResponse =
  ({ clientDataJSON, attestationObject }) => ({
    clientDataJSON: UInt8ToBase64UrlSafe(clientDataJSON),
    attestationObject: UInt8ToBase64UrlSafe(attestationObject),
  });

export const serializeAuthenticatorAssertionResponse: (response: AuthenticatorAssertionResponse) => SerializedAuthenticatorAssertionResponse =
  ({ clientDataJSON, authenticatorData, signature, userHandle }) => ({
    clientDataJSON: UInt8ToBase64UrlSafe(clientDataJSON),
    userHandle: userHandle ? UInt8ToBase64UrlSafe(userHandle) : undefined,
    signature: UInt8ToBase64UrlSafe(signature),
    authenticatorData: UInt8ToBase64UrlSafe(authenticatorData),
  });

export const serializeAuthenticatorResponse: (response: AuthenticatorResponse) => SerializedAuthenticatorResponse = cond([
  [isAuthenticatorAttestationResponse, serializeAuthenticatorAttestationResponse],
  [isAuthenticatorAssertionResponse, serializeAuthenticatorAssertionResponse],
  [TRUE, always(null)],
]);

export const serializePublicKeyCredential: (credentials: PublicKeyCredential) => SerializedPublicKeyCredential =
  ({ id, rawId, response, type }) =>
    ({
      id,
      rawId: UInt8ToBase64UrlSafe(rawId),
      response: serializeAuthenticatorResponse(response),
      type,
    });

export const isPublicKeyCredentialType: (type: CredentialType | null) => type is PublicKeyCredential = ((key: any) => key && key.type === 'public-key') as any;

export const isWebAuthnSupported = () => typeof navigator.credentials !== 'undefined';

/**
 * Simple hashing function for message to error codes.
 */
export const toSimpleHash = (input: string): number => reduce(
    // tslint:disable-next-line:no-bitwise
    (hash, char) => Math.abs(((hash << 5) - hash) + char.charCodeAt(0)),
    0,
    splitEvery(1, replace(/".*?"|'.*?'/g, '', input)),
  );
