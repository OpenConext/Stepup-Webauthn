import { useCallback } from 'react';
import { Observable, Subscription } from 'rxjs';
import { PublicKeyResponseValidator } from '../../function/http';
import { ApplicationAction, SerializedPublicKeyCredentialCreationOptions } from '../../model';
import { startRegistration } from '../../observable/startRegistration';

export const useRegistrationEffect = (dispatch: (action: ApplicationAction) => void, publicKeyOptions: SerializedPublicKeyCredentialCreationOptions, send: PublicKeyResponseValidator, whenClicked: Observable<unknown>) =>
  useCallback(
    () => {
      const subscription: Subscription = startRegistration(dispatch, publicKeyOptions, send, whenClicked);
      return () => subscription.unsubscribe();
    },
    [dispatch, publicKeyOptions, send, whenClicked],
  );
