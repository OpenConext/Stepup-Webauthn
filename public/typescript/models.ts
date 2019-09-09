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

export enum ApplicationEvent {
  // Generic
  SERIALIZE_PUBLIC_KEY_CREDENTIALS,
  PUBLIC_KEY_CREDENTIALS_SERIALIZED,
  SENDING_PUBLIC_KEY_CREDENTIALS,
  RECEIVED_SERVER_RESPONSE,

  // Registration
  DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS,
  ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED,
  REQUEST_USER_FOR_ASSERTION,

  // Authentication
  DESERIALIZE_ASSERTION_RESPONSE_OPTIONS,
  ASSERTION_RESPONSE_OPTIONS_DE_SERIALIZED,
  REQUEST_USER_FOR_ATTESTATION,
  PUBLIC_KEY_CREDENTIALS,

  // Error
  UNSUPPORTED_PUBLIC_KEY_CREDENTIALS,
  ERROR,
  UNSUPPORTED_CREDENTIAL_TYPE,
}
