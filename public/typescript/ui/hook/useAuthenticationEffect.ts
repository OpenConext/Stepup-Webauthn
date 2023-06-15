import { useCallback } from 'react';
import { Observable } from 'rxjs';
import { reloadPage } from '../../function';
import { PublicKeyResponseValidator } from '../../function/http';
import { ApplicationAction, SerializedPublicKeyCredentialRequestOptions } from '../../model';
import { authenticationObservable } from '../../observable';
import { retryWith } from '../../operator';

export const useAuthenticationEffect = (dispatch: (action: ApplicationAction) => void, publicKeyOptions: SerializedPublicKeyCredentialRequestOptions, send: PublicKeyResponseValidator, whenClicked: Observable<unknown>) =>
  useCallback(
    () => {
      const time = () => (new Date()).toISOString();
      const subscription = authenticationObservable(
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
      return () => subscription.unsubscribe();
      // tslint:disable-next-line:align
    }, [dispatch, publicKeyOptions, send, whenClicked],
  );
