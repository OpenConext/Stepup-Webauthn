import { useCallback } from 'react';
import { Observable } from 'rxjs';
import { reloadPage } from '../../function';
import { PublicKeyResponseValidator } from '../../function/http';
import { ApplicationAction, SerializedPublicKeyCredentialCreationOptions } from '../../model';
import { registrationObservable } from '../../observable';
import { retryWith } from '../../operator';

export const useRegistrationEffect = (dispatch: (action: ApplicationAction) => void, publicKeyOptions: SerializedPublicKeyCredentialCreationOptions, send: PublicKeyResponseValidator, whenClicked: Observable<unknown>) =>
  useCallback(
    () => {
      const time = () => (new Date()).toISOString();
      const subscription = registrationObservable(
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
      return () => subscription.unsubscribe();
    },
    [dispatch, publicKeyOptions, send, whenClicked],
  );
