import {
  SerializedAuthenticatorAssertionResponse,
  SerializedAuthenticatorAttestationResponse,
} from '../model';
import { serializeAuthenticatorResponse } from './AuthenticatorAttestationResponse';
import { uInt8ToBase64UrlSafe } from './common';

export const serializePublicKeyCredential: ({ id, rawId, response, type, getClientExtensionResults }: {
  id: any;
  rawId: any;
  response: any;
  type: any;
  getClientExtensionResults: any
}) => {
  getClientExtensionResults: () => AuthenticationExtensionsClientOutputs;
  response: SerializedAuthenticatorAttestationResponse | SerializedAuthenticatorAssertionResponse;
  rawId: string;
  id: string;
  type: 'public-key'
} =
  ({ id, rawId, response, type,  getClientExtensionResults }) => {
    return ({
      id,
      rawId: uInt8ToBase64UrlSafe(rawId),
      response: serializeAuthenticatorResponse(response),
      type,
      getClientExtensionResults,
    });
  };
