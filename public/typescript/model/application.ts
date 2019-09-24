
export type TranslationString = string;

export enum ApplicationEvent {
  // Generic
  NOT_SUPPORTED,
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

export interface RequestInformation {
  sari: string;
  hostname: string;
  requestId: string;
  userAgent: string;
  ipAddress: string;
  supportEmail: string;
}

export interface ErrorWithMail {
  code: string;
  error: string;
  timestamp: string;
  showMailTo: true;
  showRetry: boolean;
}

export interface ErrorWithoutMail {
  code: string;
  timestamp: string;
  showMailTo: false;
  showRetry: boolean;
}

export function errorWithMailTo(error: ErrorInformation): error is ErrorWithMail {
  return error.showMailTo;
}

export type ErrorInformation = ErrorWithMail | ErrorWithoutMail;

export interface ApplicationState {
  requestInformation: RequestInformation;
  errorInfo: ErrorInformation | null;
  message: TranslationString;

  // Debug values, not used in application.
  clientDataJSON?: string;
}

export interface ApplicationAction {
  type: ApplicationEvent;
  value: unknown;
  timestamp: string;
}

export type FireApplicationEvent = (event: ApplicationEvent) => (value: unknown) => void;
