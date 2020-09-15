import { SerializedPublicKeyCredential } from '../model';
import { serializeAuthenticatorResponse } from './AuthenticatorAttestationResponse';
import { uInt8ToBase64UrlSafe } from './common';

export const serializePublicKeyCredential: (credentials: PublicKeyCredential) => SerializedPublicKeyCredential =
  ({ id, rawId, response, type,  getClientExtensionResults }) => {
    return ({
      id,
      rawId: uInt8ToBase64UrlSafe(rawId),
      response: serializeAuthenticatorResponse(response),
      type,
      getClientExtensionResults,
    });
  };
