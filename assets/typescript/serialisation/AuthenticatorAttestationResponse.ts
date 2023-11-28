import { always, complement, cond, isNil, propSatisfies, T as TRUE } from 'ramda';
import {
  SerializedAuthenticatorAssertionResponse,
  SerializedAuthenticatorResponse,
} from '../model';
import { uInt8ToBase64UrlSafe } from './common';

export const isAuthenticatorAttestationResponse: (response: AuthenticatorResponse) => response is AuthenticatorAttestationResponse = propSatisfies(complement(isNil), 'attestationObject') as any;
export const isAuthenticatorAssertionResponse: (response: AuthenticatorResponse) => response is AuthenticatorAssertionResponse = propSatisfies(complement(isNil), 'signature') as any;

export const serializeAuthenticatorAttestationResponse: ({ clientDataJSON, attestationObject }: {
  clientDataJSON: any;
  attestationObject: any
}) => { clientDataJSON: string; attestationObject: string } =
  ({ clientDataJSON, attestationObject }) => {
    return ({
      clientDataJSON: uInt8ToBase64UrlSafe(clientDataJSON),
      attestationObject: uInt8ToBase64UrlSafe(attestationObject),
    });
  };

export const serializeAuthenticatorAssertionResponse: (response: AuthenticatorAssertionResponse) => SerializedAuthenticatorAssertionResponse =
  ({ clientDataJSON, authenticatorData, signature, userHandle }) => ({
    clientDataJSON: uInt8ToBase64UrlSafe(clientDataJSON),
    userHandle: userHandle ? uInt8ToBase64UrlSafe(userHandle) : undefined,
    signature: uInt8ToBase64UrlSafe(signature),
    authenticatorData: uInt8ToBase64UrlSafe(authenticatorData),
  });

export const serializeAuthenticatorResponse: (response: AuthenticatorResponse) => SerializedAuthenticatorResponse = cond([
  [isAuthenticatorAttestationResponse, serializeAuthenticatorAttestationResponse],
  [isAuthenticatorAssertionResponse, serializeAuthenticatorAssertionResponse],
  [TRUE, always(null)],
]);
