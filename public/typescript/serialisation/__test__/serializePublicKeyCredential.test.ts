import { serializePublicKeyCredential } from '../SerializedPublicKeyCredential';
import 'jest';

const getClientExtensionResultsMock = jest.fn();

it('serializePublicKeyCredential', () => {

  // @ts-ignore
  const credentials: PublicKeyCredential = {
    response: {
      clientDataJSON: Uint8Array.from([1, 2, 3]),
    },
    rawId: Uint8Array.from([7, 8, 9]),
    type: 'public-key',
    id: '1234',
    getClientExtensionResults: getClientExtensionResultsMock,
  };
  expect(serializePublicKeyCredential(credentials)).toStrictEqual({
    id: '1234',
    rawId: 'BwgJ',
    response: null,
    type: 'public-key',
    getClientExtensionResults: getClientExtensionResultsMock,
  });
});

it('serializePublicKeyCredential with AuthenticatorAttestationResponse', () => {
  const credentials: Omit<PublicKeyCredential, 'response'> & { response: AuthenticatorAttestationResponse } = {
    // @ts-ignore
    response: {
      clientDataJSON: Uint8Array.from([1, 2, 3]),
      attestationObject: Uint8Array.from([4, 5, 6]),
    },
    rawId: Uint8Array.from([7, 8, 9]),
    type: 'public-key',
    id: '1234',
    getClientExtensionResults: getClientExtensionResultsMock,
  };

  expect(serializePublicKeyCredential(credentials)).toStrictEqual({
    id: '1234',
    rawId: 'BwgJ',
    response: {
      attestationObject: 'BAUG',
      clientDataJSON: 'AQID',
    },
    type: 'public-key',
    getClientExtensionResults: getClientExtensionResultsMock,
  });
});

it('serializePublicKeyCredential with AuthenticatorAssertionResponse', () => {
  // @ts-ignore
  const credentials: Omit<PublicKeyCredential, 'response'> & { response: AuthenticatorAssertionResponse } = {
    response: {
      clientDataJSON: Uint8Array.from([1, 2, 3]),
      authenticatorData: Uint8Array.from([4, 5, 6]),
      signature: Uint8Array.from([10, 11, 12]),
      userHandle: Uint8Array.from([11, 12, 13]),
    },
    rawId: Uint8Array.from([7, 8, 9]),
    type: 'public-key',
    getClientExtensionResults: getClientExtensionResultsMock,
    id: '1234',
  };

  expect(serializePublicKeyCredential(credentials)).toStrictEqual({
    id: '1234',
    rawId: 'BwgJ',
    response: {
      authenticatorData: 'BAUG',
      clientDataJSON: 'AQID',
      signature: 'CgsM',
      userHandle: 'CwwN',
    },
    type: 'public-key',
    getClientExtensionResults: getClientExtensionResultsMock,
  });
});
