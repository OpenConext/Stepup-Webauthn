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

export interface SerializedAuthenticatorResponse extends Omit<AuthenticatorResponse, 'clientDataJSON'> {
  clientDataJSON: string;
  attestationObject: string | null;
}

export interface SerializedPublicKeyCredential extends Omit<PublicKeyCredential, 'rawId' | 'response'> {
  rawId: string;
  response: SerializedAuthenticatorResponse;
}

export enum RegistrationState {
  DESERIALIZE_ATTESTATION_RESPONSE_OPTIONS,
  ATTESTATION_RESPONSE_OPTIONS_DE_SERIALIZED,
  REQUEST_USER_FOR_ATTESTATION,
  PUBLIC_KEY_CREDENTIALS,
  SERIALIZE_PUBLIC_KEY_CREDENTIALS,
  PUBLIC_KEY_CREDENTIALS_SERIALIZED,
  SENDING_PUBLIC_KEY_CREDENTIALS,
  RECEIVED_SERVER_RESPONSE,
  RECEIVED_SERVER_ERROR_RESPONSE,
  UNSUPORTED_PUBLIC_KEY_CREDENTIALS,
  ERROR,
}
