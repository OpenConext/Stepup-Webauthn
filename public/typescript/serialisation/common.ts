import { anyPass, complement, isEmpty, isNil, lensPath, map, over, pickBy, unless } from 'ramda';
import { decode, encode } from 'urlsafe-base64';

export const base64UrlSafeToUInt8 = (base64: string): BufferSource => Uint8Array.from(decode(base64)).buffer;
export const uInt8ToBase64UrlSafe = (buffer: BufferSource): string => encode(Buffer.from(buffer as any));

export const idLens = lensPath(['id']);
export const base64UrlSafeToUInt8Id: <T extends { id: string }>(entity: T) => Omit<T, 'id'> & { id: BufferSource } = over(idLens, base64UrlSafeToUInt8) as any;
export const base64UrlSafeToUInt8Ids = map(base64UrlSafeToUInt8Id);
export const optionalBase64UrlSafeToUInt8Ids = unless(isNil, base64UrlSafeToUInt8Ids);
export const removeEmptyAndUndefined = pickBy(complement(anyPass([isNil, isEmpty])));
