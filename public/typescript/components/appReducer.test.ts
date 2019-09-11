import 'jest';
import { appReducer } from '../appReducer';
import { ApplicationEvent, ApplicationState, RequestInformation } from '../models';

describe('appReducer', () => {
  const requestInformation: RequestInformation = {
    hostname: 'webauthn.test',
    ipAddress: '192.168.77.1',
    requestId: 'e54e303f5032c52762175f0b99b69168',
    sari: '_7aff65182b9eb8119ef3531e07b8b6d02d5f7d289e4ab67489548b21c52f',
    supportEmail: 'support@support.nl',
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
  };

  describe('ApplicationEvent.NOT_SUPPORTED', () => {
    let initialState: ApplicationState | undefined;
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.NOT_SUPPORTED };
    beforeEach(() => {
      initialState = {
        errorInfo: null,
        message: 'initial',
        requestInformation,
      };
    });
    it('Should not alter initial state', () => {
      const result = appReducer(initialState!, action);
      expect(result).not.toBe(initialState);
    });

    it('Should return "not supported" and timestamp string', () => {
      const result = appReducer(initialState!, action);
      expect(result.message).toEqual('status.webauthn_not_supported');
      expect(result.errorInfo!.timestamp).toEqual('now');
    });

    it('Should not display mailto and retry buttons', () => {
      const result = appReducer(initialState!, action);
      expect(result.errorInfo!.showMailTo).toBeFalsy();
      expect(result.errorInfo!.showRetry).toBeFalsy();
    });
  });

  describe('ApplicationEvent.ERROR', () => {
    let initialState: ApplicationState | undefined;
    const action = { value: null, timestamp: 'now', type: ApplicationEvent.ERROR };
    beforeEach(() => {
      initialState = {
        errorInfo: null,
        message: 'initial',
        requestInformation,
      };
    });
    it('Should not alter initial state', () => {
      const result = appReducer(initialState!, action);
      expect(result).not.toBe(initialState);
    });

    it('Should return "general error" and timestamp string', () => {
      const result = appReducer(initialState!, action);
      expect(result.message).toEqual('status.general_error');
      expect(result.errorInfo!.timestamp).toEqual('now');
    });

    it('Should display mailto and retry buttons', () => {
      const result = appReducer(initialState!, action);
      expect(result.errorInfo!.showMailTo).toBeTruthy();
      expect(result.errorInfo!.showRetry).toBeTruthy();
    });
  });

});
