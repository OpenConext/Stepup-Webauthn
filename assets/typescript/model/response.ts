export type ServiceResponseStatus = 'deviceNotSupported' |
  'noRegistrationRequired' |
  'noAuthenticationRequired' |
  'missingAttestationStatement' |
  'invalid' |
  'error' |
  'ok';

export interface ServerResponse {
  status: ServiceResponseStatus;
  error_code?: string;
}
