import { isNil, lensPath, map, over, unless } from 'ramda';
import { decode, encode } from 'urlsafe-base64';
import { SerializedPublicKeyCredentialCreationOptions } from './models';

export const Base64UrlSafeToUInt8 = (base64: string): BufferSource => Uint8Array.from(decode(base64));
export const UInt8ToBase64UrlSafe = (buffer: BufferSource): string => encode(Buffer.from(buffer as any));

export const idLens = lensPath(['id']);
export const Base64UrlSafeToUInt8Id: <T extends { id: string }>(entity: T) => Omit<T, 'id'> & { id: BufferSource } = over(idLens, Base64UrlSafeToUInt8) as any;
export const Base64UrlSafeToUInt8Ids = map(Base64UrlSafeToUInt8Id);
export const optionalBase64UrlSafeToUInt8Ids = unless(isNil, Base64UrlSafeToUInt8Ids);

export const deSerializedPublicKeyCredentialCreationOptions: (options: SerializedPublicKeyCredentialCreationOptions) => PublicKeyCredentialCreationOptions = ({ rp, user, challenge, extensions, attestation, authenticatorSelection, timeout, pubKeyCredParams, excludeCredentials }) =>
  ({
    rp,
    user: Base64UrlSafeToUInt8Id(user),
    challenge: Base64UrlSafeToUInt8(challenge),
    pubKeyCredParams,
    timeout,
    excludeCredentials: optionalBase64UrlSafeToUInt8Ids(excludeCredentials) as any,
    authenticatorSelection,
    attestation,
    extensions,
  });
