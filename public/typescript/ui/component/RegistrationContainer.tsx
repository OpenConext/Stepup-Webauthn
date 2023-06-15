import React, { FC, useEffect } from 'react';
import { RequestInformation, SerializedPublicKeyCredentialCreationOptions, TranslationString } from '../../model';
import { useAppReducer, useClickable, useRegistrationEffect } from '../hook';
import { useVerifyPublicKeyCredentials } from '../hook/useVerifyPublicKeyCredentials';
import { App } from './App';
import { Subscription } from 'rxjs';
import { startRegistration } from './startRegistration';

export interface RegistrationContainerProps {
  t: (key: TranslationString) => string;
  publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
  requestInformation: RequestInformation;
  responseUrl: string;
}

export const RegistrationContainer: FC<RegistrationContainerProps> = ({ t, responseUrl, publicKeyOptions, requestInformation }) => {

  const [state, dispatch] = useAppReducer(requestInformation, 'status.registration_initial');
  const { message, errorInfo, started } = state;
  const [click, clicked] = useClickable();
  const verify = useVerifyPublicKeyCredentials(responseUrl);
  const onStart = useRegistrationEffect(dispatch, publicKeyOptions, verify, clicked);

  useEffect(() => {
    const subscription: Subscription = startRegistration(dispatch, publicKeyOptions, verify, clicked);
    return () => {
      subscription.unsubscribe();
    };
  },        [dispatch, publicKeyOptions, verify, clicked]);

  return <App started={started} startMessage="registration.start_button" message={message} errorInfo={errorInfo} requestInformation={requestInformation} t={t} onClick={click} onStart={onStart} />;
};
