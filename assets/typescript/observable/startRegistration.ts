import { Observable } from 'rxjs';
import { reloadPage } from '../function';
import { PublicKeyResponseValidator } from '../function/http';
import { ApplicationAction, SerializedPublicKeyCredentialCreationOptions } from '../model';
import { registrationObservable } from './index';
import { retryWith } from '../operator';

export const startRegistration = (dispatch: (action: ApplicationAction) => void, publicKeyOptions: SerializedPublicKeyCredentialCreationOptions, send: PublicKeyResponseValidator, whenClicked: Observable<unknown>) => {
  const time = () => (new Date()).toISOString();
  return registrationObservable(
    send,
    publicKeyOptions,
    (options) => navigator.credentials.create(options),
    time,
  )
    .pipe(retryWith((type) => (value) => dispatch({ type, value, timestamp: time() }), whenClicked))
    .subscribe({
      next: dispatch,
      complete: reloadPage,
    });
};
