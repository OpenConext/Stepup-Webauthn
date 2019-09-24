import 'jest';
import { ApplicationAction, ApplicationEvent, ApplicationState, RequestInformation } from '../../model';
import { appReducer } from '../appReducer';
import { encode } from 'urlsafe-base64';

describe('appReducer', () => {
  let initialState: ApplicationState | undefined;
  const requestInformation: RequestInformation = {
    hostname: 'webauthn.test',
    ipAddress: '192.168.77.1',
    requestId: 'e54e303f5032c52762175f0b99b69168',
    sari: '_7aff65182b9eb8119ef3531e07b8b6d02d5f7d289e4ab67489548b21c52f',
    supportEmail: 'support@support.nl',
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
  };
  beforeEach(() => {
    initialState = {
      errorInfo: null,
      message: 'initial',
      requestInformation,
    };
  });

  function state() {
    return initialState!;
  }

  describe('ApplicationEvent.REQUEST_USER_FOR_ASSERTION', () => {
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.REQUEST_USER_FOR_ASSERTION };
    shouldNotAlterState(state, action);
    shouldNothaveAnError(state, action);
    shouldSetMessageTo(state, action, 'status.authentication_initial');
  });

  describe('ApplicationEvent.REQUEST_USER_FOR_ATTESTATION', () => {
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.REQUEST_USER_FOR_ATTESTATION };
    shouldNotAlterState(state, action);
    shouldNothaveAnError(state, action);
    shouldSetMessageTo(state, action, 'status.registration_initial');
  });

  describe('ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED', () => {
    const value = {
      response: {
        clientDataJSON: encode(new Buffer('123')),
      },
    };
    const action = { value, timestamp: 'now', type: ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED };
    shouldNotAlterState(state, action);
    shouldNothaveAnError(state, action);
    it('Should decode clientDataJSON', () => {
      const result = appReducer(initialState!, action);
      expect(result.clientDataJSON).toEqual('123');
    });
  });

  describe('ApplicationEvent.NOT_SUPPORTED', () => {
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.NOT_SUPPORTED };
    shouldNotAlterState(state, action);
    shouldNotDisplayRetryAndMailToButton(state, action);
    shouldSetMessageTo(state, action, 'status.webauthn_not_supported');
  });

  describe('ApplicationEvent.ERROR', () => {
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.ERROR };
    shouldNotAlterState(state, action);
    shouldDisplayMailAndRetryButton(state, action);
    shouldSetMessageTo(state, action, 'status.general_error');
  });
});

function shouldNothaveAnError(initialState: () => ApplicationState, action: ApplicationAction) {
  it('Should not have an error', () => {
    const result = appReducer(initialState(), action);
    expect(result.errorInfo).toBeNull();
  });
}

function shouldNotDisplayRetryAndMailToButton(initialState: () => ApplicationState, action: ApplicationAction) {
  it('Should not display retry and mailto button', () => {
    const result = appReducer(initialState(), action);
    expect(result.errorInfo!.showRetry).toBeFalsy();
    expect(result.errorInfo!.showMailTo).toBeFalsy();
  });
}

function shouldDisplayMailAndRetryButton(initialState: () => ApplicationState, action: ApplicationAction) {
  it('Should display mailto and retry button', () => {
    const result = appReducer(initialState(), action);
    expect(result.errorInfo!.showRetry).toBeTruthy();
    expect(result.errorInfo!.showMailTo).toBeTruthy();
  });
}

function shouldNotAlterState(initialState: () => ApplicationState, action: ApplicationAction) {
  it('Should not alter initial state', () => {
    const result = appReducer(initialState(), action);
    expect(result).not.toBe(initialState);
  });
}

function shouldSetMessageTo(initialState: () => ApplicationState, action: ApplicationAction, message: string) {
  it(`Should display ${message}`, () => {
    const result = appReducer(initialState(), action);
    expect(result.message).toEqual(message);
  });
}
