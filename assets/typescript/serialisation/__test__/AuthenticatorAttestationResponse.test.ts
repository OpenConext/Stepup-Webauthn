import { SerializedPublicKeyCredentialRequestOptions } from '../../model';
import { deSerializedPublicKeyCredentialRequestOptions } from '../PublicKeyCredentialCreationOptions';

it('deSerializedPublicKeyCredentialRequestOptions', () => {
  const options: SerializedPublicKeyCredentialRequestOptions = {
    challenge: 'Y-',
    rpId: 'webauthn.test',
    userVerification: 'required',
    allowCredentials: [{
      id: 'AI-Q',
      type: 'public-key',
      transports: [],
    }],
    timeout: 30000,
  };
  expect(deSerializedPublicKeyCredentialRequestOptions(options)).toStrictEqual({
    challenge: Uint8Array.from([99]).buffer,
    rpId: 'webauthn.test',
    userVerification: 'required',
    allowCredentials: [{
      id: Uint8Array.from([0, 143, 144]).buffer,
      type: 'public-key',
      transports: [],
    }],
    timeout: 30000,
  });
});
