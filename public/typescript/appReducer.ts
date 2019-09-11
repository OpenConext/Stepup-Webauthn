import { AxiosResponse } from 'axios';
import { complement, isNil, pathSatisfies } from 'ramda';
import { Reducer } from 'react';
import { decode } from 'urlsafe-base64';
import { createErrorCode } from './functions';
import { Action, ApplicationEvent, ApplicationState, SerializedPublicKeyCredential } from './models';

export type AppReducer = Reducer<ApplicationState, Action>;

export const appReducer: AppReducer = (state, { value, type, timestamp }) => {
  const { requestInformation } = state;
  switch (type) {
    case ApplicationEvent.NOT_SUPPORTED:
      return {
        requestInformation,
        message: 'status.webauthn_not_supported',
        errorInfo: {
          timestamp,
          code: createErrorCode('webauthn_not_supported'),
          showMailTo: false,
          showRetry: false,
        },
      };

    case ApplicationEvent.REQUEST_USER_FOR_ATTESTATION:
      return {
        requestInformation,
        message: 'status.registration_initial',
        errorInfo: null,
      };

    case ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value as any;
      return {
        ...state,
        clientDataJSON: decode(credentials.response.clientDataJSON).toString(),
      };

    case ApplicationEvent.REQUEST_USER_FOR_ASSERTION:
      return {
        requestInformation,
        message: 'status.authentication_initial',
        errorInfo: null,
      };

    case ApplicationEvent.ERROR:
      if (isServerResponseError(value)) {
        return handleServerResponse(state, timestamp, value.response);
      }
      return {
        requestInformation,
        message: 'status.general_error',
        errorInfo: {
          timestamp,
          code: createErrorCode(`${value}`),
          showRetry: true,
          showMailTo: true,
        },
      };
  }

  return state;
};

const isServerResponseError: (value: unknown) => value is { response: AxiosResponse } = pathSatisfies(complement(isNil), ['response']) as any;

/**
 * {@see \App\ValidationJsonResponse} for all server response types
 */
export const handleServerResponse = (state: ApplicationState, timestamp: string, response: AxiosResponse): ApplicationState => {
  let status: string | undefined = response.data.status;
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
  const code: string = response.data.error_code;
  switch (status) {
    case 'deviceNotSupported':
      return {
        requestInformation,
        message: 'status.authenticator_not_supported',
        errorInfo: {
          code,
          timestamp,
          showMailTo: false,
          showRetry: false,
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
