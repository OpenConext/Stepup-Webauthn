import { AxiosResponse } from 'axios';
import { complement, isNil, pathSatisfies } from 'ramda';
import { ApplicationState } from '../model';
import { ServerResponse, ServiceResponseStatus } from '../model/response';

export const isServerResponseError: (value: unknown) => value is { response: ServerResponse & AxiosResponse } = pathSatisfies(complement(isNil), ['response']) as any;

/**
 * {@see \App\ValidationJsonResponse} for all server response types
 */
export const serverResponseErrorReducer = (state: ApplicationState, timestamp: string, response: ServerResponse & AxiosResponse): ApplicationState => {
  let status: ServiceResponseStatus | undefined = response.status as any;
  const { requestInformation } = state;
  if (!status) {
    // Last resort, something went completely wrong, show server response page.
    const contentType: string = response.headers['content-type'];
    if (contentType.indexOf('text/html;') >= 0) {
      document.body.innerHTML = response.data;
      throw new Error('Unrecoverable application error, server error page shown');
    }
    status = 'error';
  }
  const code = response.error_code as any;
  switch (status) {
    case 'deviceNotSupported':
      return {
        requestInformation,
        message: 'status.authenticator_not_supported',
        errorInfo: {
          code,
          timestamp,
          showMailTo: false,
          showRetry: true,
        },
      };
    case 'noRegistrationRequired':
    case 'noAuthenticationRequired':
      return {
        requestInformation,
        message: 'status.no_active_request',
        errorInfo: {
          code,
          timestamp,
          showMailTo: false,
          showRetry: false,
        },
      };
    case 'missingAttestationStatement':
      return {
        requestInformation,
        message: 'missing_attestation_statement',
        errorInfo: {
          code,
          timestamp,
          showMailTo: false,
          showRetry: true,
        },
      };
    case 'invalid':
    case 'error':
      return {
        requestInformation,
        message: 'status.general_error',
        errorInfo: {
          code,
          timestamp,
          showMailTo: false,
          showRetry: true,
        },
      };
  }
  return state;
};
