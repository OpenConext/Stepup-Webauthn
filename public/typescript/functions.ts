import { anyPass, complement, isEmpty, isNil, lensPath, map, over, pickBy, propSatisfies, unless } from 'ramda';
import { decode, encode } from 'urlsafe-base64';
import { SerializedPublicKeyCredential, SerializedPublicKeyCredentialCreationOptions } from './models';

export const Base64UrlSafeToUInt8 = (base64: string): BufferSource => Uint8Array.from(decode(base64));
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

export const isAuthenticatorAttestationResponse: (response: AuthenticatorResponse | AuthenticatorAttestationResponse) => response is AuthenticatorAttestationResponse = propSatisfies(complement(isNil), 'attestationObject') as any;

export const serializePublicKeyCredential: (credentials: PublicKeyCredential) => SerializedPublicKeyCredential =
  ({ id, rawId, response, type }) =>
    ({
      id,
      rawId: UInt8ToBase64UrlSafe(rawId),
      response: {
        clientDataJSON: UInt8ToBase64UrlSafe(response.clientDataJSON),
        attestationObject: isAuthenticatorAttestationResponse(response) ? UInt8ToBase64UrlSafe(response.attestationObject) : null,
      },
      type,
    });

export const isPublicKeyCredentialType: (type: CredentialType | null) => type is PublicKeyCredential = ((key: any) => key && key.type === 'public-key') as any;
