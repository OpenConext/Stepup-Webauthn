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
