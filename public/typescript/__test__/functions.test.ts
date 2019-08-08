import { set } from 'ramda';
import {
  Base64UrlSafeToUInt8,
  Base64UrlSafeToUInt8Id,
  Base64UrlSafeToUInt8Ids,
  deSerializedPublicKeyCredentialCreationOptions,
  idLens,
  isAuthenticatorAttestationResponse,
  optionalBase64UrlSafeToUInt8Ids,
  removeEmptyAndUndefined,
  serializePublicKeyCredential,
} from '../functions';
import { SerializedPublicKeyCredentialCreationOptions } from '../models';

it('base64ToUInt8', () => {
  expect(Base64UrlSafeToUInt8('z4Ag4oiIIOKEnQ==')).toEqual(new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157]));
});

it('idLens', () => {
  expect(set(idLens, 2, { id: 1, foo: 'bar' })).toEqual({
    id: 2,
    foo: 'bar',
  });
});

it('base64ToUInt8Id', () => {
  expect(Base64UrlSafeToUInt8Id({ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' })).toEqual({
    id: new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157]),
    foo: 'bar',
  });
});

it('base64ToUInt8Ids', () => {
  expect(Base64UrlSafeToUInt8Ids([{ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' }, {
    id: 'NDM1MzQ1MzQ=',
    bar: 'foo',
  }])).toEqual([
    {
      id: new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157]),
      foo: 'bar',
    },
    {
      id: new Uint8Array([52, 51, 53, 51, 52, 53, 51, 52]),
      bar: 'foo',
    },
  ]);
});

it('optionalBase64ToUInt8Ids can convert', () => {
  expect(optionalBase64UrlSafeToUInt8Ids([{ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' }, {
    id: 'NDM1MzQ1MzQ=',
    bar: 'foo',
  }])).toEqual([
    {
      id: new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157]),
      foo: 'bar',
    },
    {
      id: new Uint8Array([52, 51, 53, 51, 52, 53, 51, 52]),
      bar: 'foo',
    },
  ]);
});

it('optionalBase64ToUInt8Ids can be empty', () => {
  expect(optionalBase64UrlSafeToUInt8Ids(undefined)).toEqual(undefined);
});

it('removeEmptyAndUndefined', () => {
  expect(removeEmptyAndUndefined({
    test: undefined,
    foo: [],
    bar: 'bar',
  })).toStrictEqual({
    bar: 'bar',
  });
});

it('deSerializedPublicKeyCredentialCreationOptions', () => {
  const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions = {
    rp: { name: 'My Super Secured Application', id: 'foo.example.com' },
    pubKeyCredParams: [{ type: 'public-key', alg: -7 }],
    challenge: '237glVYXXQoFNJCF_7fgCFwKneTN5QsWWR9hXT41rTY',
    attestation: 'none',
    user: {
      name: '@cypher-Angel-3000',
      id: 'MTIzZTQ1NjctZTg5Yi0xMmQzLWE0NTYtNDI2NjU1NDQwMDAw',
      displayName: 'Mighty Mike',
    },
    authenticatorSelection: { requireResidentKey: false, userVerification: 'preferred' },
    excludeCredentials: [{ type: 'public-key', id: 'QUJDREVGR0g=' }],
    extensions: { loc: true },
    timeout: 20000,
  };
  expect(deSerializedPublicKeyCredentialCreationOptions(publicKeyOptions)).toMatchSnapshot();
});

it('deSerializedPublicKeyCredentialCreationOptions should remove empty options', () => {
  const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions = {
    rp: { name: 'My Super Secured Application', id: 'foo.example.com' },
    pubKeyCredParams: [],
    challenge: '237glVYXXQoFNJCF_7fgCFwKneTN5QsWWR9hXT41rTY',
    attestation: 'none',
    user: {
      name: '@cypher-Angel-3000',
      id: 'MTIzZTQ1NjctZTg5Yi0xMmQzLWE0NTYtNDI2NjU1NDQwMDAw',
      displayName: 'Mighty Mike',
    },
    authenticatorSelection: { requireResidentKey: false, userVerification: 'preferred' },
    excludeCredentials: undefined,
    extensions: { loc: true },
    timeout: 20000,
  };
  expect(deSerializedPublicKeyCredentialCreationOptions(publicKeyOptions)).toMatchSnapshot();
});

it('isAuthenticatorAttestationResponse', () => {
  expect(isAuthenticatorAttestationResponse({
    clientDataJSON: Uint8Array.from([1, 2, 3]),
  })).toBeFalsy();
  expect(isAuthenticatorAttestationResponse({
    clientDataJSON: Uint8Array.from([1, 2, 3]),
    attestationObject: Uint8Array.from([4, 5, 6]),
  })).toBeTruthy();
});

it('serializePublicKeyCredential', () => {
  const credentials: PublicKeyCredential = {
    response: {
      clientDataJSON: Uint8Array.from([1, 2, 3]),
    },
    rawId: Uint8Array.from([7, 8, 9]),
    type: 'public-key',
    id: '1234',
  };
  expect(serializePublicKeyCredential(credentials)).toStrictEqual({
    id: '1234',
    rawId: 'BwgJ',
    response: {
      attestationObject: null,
      clientDataJSON: 'AQID',
    },
    type: 'public-key',
  });
});

it('serializePublicKeyCredential with AttestationResponse', () => {
  const credentials: Omit<PublicKeyCredential, 'response'> & { response: AuthenticatorAttestationResponse } = {
    response: {
      clientDataJSON: Uint8Array.from([1, 2, 3]),
      attestationObject: Uint8Array.from([4, 5, 6]),
    },
    rawId: Uint8Array.from([7, 8, 9]),
    type: 'public-key',
    id: '1234',
  };

  expect(serializePublicKeyCredential(credentials)).toStrictEqual({
    id: '1234',
    rawId: 'BwgJ',
    response: {
      attestationObject: 'BAUG',
      clientDataJSON: 'AQID',
    },
    type: 'public-key',
  });
});
