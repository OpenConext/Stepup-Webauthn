import { SerializedPublicKeyCredentialCreationOptions, SerializedPublicKeyCredentialRequestOptions } from '../model';
import {
  base64UrlSafeToUInt8,
  base64UrlSafeToUInt8Id,
  optionalBase64UrlSafeToUInt8Ids,
  removeEmptyAndUndefined,
} from './common';

export const deSerializedPublicKeyCredentialCreationOptions: (options: SerializedPublicKeyCredentialCreationOptions) => PublicKeyCredentialCreationOptions =
  ({ rp, user, challenge, extensions, attestation, authenticatorSelection, timeout, pubKeyCredParams, excludeCredentials }) =>
    removeEmptyAndUndefined(({
      rp,
      user: base64UrlSafeToUInt8Id(user),
      challenge: base64UrlSafeToUInt8(challenge),
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
      challenge: base64UrlSafeToUInt8(challenge),
      timeout,
      allowCredentials: optionalBase64UrlSafeToUInt8Ids(allowCredentials),
      userVerification,
      extensions,
    }));
