import { Observable } from 'rxjs';
import { reloadPage } from '../function';
import { PublicKeyResponseValidator } from '../function/http';
import { ApplicationAction, SerializedPublicKeyCredentialRequestOptions } from '../model';
import { authenticationObservable } from './index';
import { retryWith } from '../operator';

export const startAuthentication = (dispatch: (action: ApplicationAction) => void, publicKeyOptions: SerializedPublicKeyCredentialRequestOptions, send: PublicKeyResponseValidator, whenClicked: Observable<unknown>) => {
  const time = () => (new Date()).toISOString();
  return authenticationObservable(
    send,
    publicKeyOptions,
    (options) => navigator.credentials.get(options),
    time,
  )
    .pipe(retryWith((type) => (value) => dispatch({ type, value, timestamp: time() }), whenClicked))
    .subscribe({
      next: dispatch,
      complete: reloadPage,
    });
};
