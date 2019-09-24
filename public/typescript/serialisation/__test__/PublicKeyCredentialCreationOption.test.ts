import { SerializedPublicKeyCredentialCreationOptions } from '../../model';
import { deSerializedPublicKeyCredentialCreationOptions } from '../PublicKeyCredentialCreationOptions';

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
