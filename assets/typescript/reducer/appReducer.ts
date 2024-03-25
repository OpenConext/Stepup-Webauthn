import { Reducer } from 'react';
import { decode } from 'urlsafe-base64';
import { createErrorCode } from '../function';
import { ApplicationAction, ApplicationEvent, ApplicationState, SerializedPublicKeyCredential } from '../model';
import { isServerResponseError, serverResponseErrorReducer } from './serverResponseErrorReducer';

export type AppReducer = Reducer<ApplicationState, ApplicationAction>;

export const appReducer: AppReducer = (state, { value, type, timestamp }) => {
  const { requestInformation } = state;
  switch (type) {
    case ApplicationEvent.NOT_SUPPORTED:
      return {
        started: true,
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
        started: true,
        requestInformation,
        message: 'status.registration_initial',
        errorInfo: null,
      };

    case ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value as any;
      return {
        ...state,
        started: true,
        clientDataJSON: decode(credentials.response.clientDataJSON).toString(),
      };

    case ApplicationEvent.REQUEST_USER_FOR_ASSERTION:
      return {
        started: true,
        requestInformation,
        message: 'status.authentication_initial',
        errorInfo: null,
      };

    case ApplicationEvent.ERROR:
      if (isServerResponseError(value)) {
        return serverResponseErrorReducer(state, timestamp, value.response);
      }
      return {
        started: true,
        requestInformation,
        message: 'status.general_error',
        errorInfo: {
          timestamp,
          code: createErrorCode(`${value}`),
          error: `${value}`,
          showRetry: true,
          showMailTo: true,
        },
      };
  }

  return state;
};
