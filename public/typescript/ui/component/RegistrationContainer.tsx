import React, { FC } from 'react';
import { RequestInformation, SerializedPublicKeyCredentialCreationOptions, TranslationString } from '../../model';
import { useAppReducer, useClickable, useRegistrationEffect } from '../hook';
import { useVerifyPublicKeyCredentials } from '../hook/useVerifyPublicKeyCredentials';
import { App } from './App';

export interface RegistrationContainerProps {
  t: (key: TranslationString) => string;
  publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
  requestInformation: RequestInformation;
  responseUrl: string;
}

export const RegistrationContainer: FC<RegistrationContainerProps> = ({ t, responseUrl, publicKeyOptions, requestInformation }) => {
  const [state, dispatch] = useAppReducer(requestInformation, 'status.registration_initial');
  const { message, errorInfo } = state;
  const [click, clicked] = useClickable();
  const verify = useVerifyPublicKeyCredentials(responseUrl);
  useRegistrationEffect(dispatch, publicKeyOptions, verify, clicked);
  return <App message={message} errorInfo={errorInfo} requestInformation={requestInformation} t={t} onClick={click} />;
};
