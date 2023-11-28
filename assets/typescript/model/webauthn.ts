export interface SerializedPublicKeyCredentialUserEntity extends Omit<PublicKeyCredentialUserEntity, 'id'> {
  id: string;
}

export interface SerializedPublicKeyCredentialDescriptor extends Omit<PublicKeyCredentialDescriptor, 'id'> {
  id: string;
}

export interface SerializedPublicKeyCredentialCreationOptions extends Omit<PublicKeyCredentialCreationOptions, 'challenge' | 'excludeCredentials' | 'user'> {
  challenge: string;
  user: SerializedPublicKeyCredentialUserEntity;
  excludeCredentials?: SerializedPublicKeyCredentialDescriptor[];
}

export interface SerializedPublicKeyCredentialRequestOptions extends Omit<PublicKeyCredentialRequestOptions, 'challenge' | 'allowCredentials'> {
  challenge: string;
  allowCredentials?: SerializedPublicKeyCredentialDescriptor[];
}

export interface SerializedAuthenticatorAttestationResponse extends Omit<AuthenticatorAttestationResponse, 'clientDataJSON' | 'attestationObject'> {
  clientDataJSON: string;
  attestationObject: string | undefined;
}

export interface SerializedAuthenticatorAssertionResponse extends Omit<AuthenticatorAssertionResponse, 'clientDataJSON' | 'userHandle' | 'signature' | 'authenticatorData'> {
  clientDataJSON: string;
  userHandle: string | undefined;
  signature: string;
  authenticatorData: string;
}

export type SerializedAuthenticatorResponse =
  SerializedAuthenticatorAttestationResponse
  | SerializedAuthenticatorAssertionResponse;

export interface SerializedPublicKeyCredential extends Omit<PublicKeyCredential, 'rawId' | 'response'> {
  rawId: string;
  response: SerializedAuthenticatorResponse;
}
