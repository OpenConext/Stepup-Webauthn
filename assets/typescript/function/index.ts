export * from './createErrorCode';
export * from './translations';

export const isPublicKeyCredentialType: (type: CredentialType | null) => type is PublicKeyCredential = ((key: any) => key && key.type === 'public-key') as any;

export const isWebAuthnSupported = () => typeof navigator.credentials !== 'undefined';

export const reloadPage = () => window.location.reload();
