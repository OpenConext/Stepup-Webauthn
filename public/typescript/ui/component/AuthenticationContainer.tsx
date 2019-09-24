import React, { FC } from 'react';
import { RequestInformation, SerializedPublicKeyCredentialRequestOptions, TranslationString } from '../../model';
import { useAppReducer, useAuthenticationEffect, useClickable } from '../hook';
import { useVerifyPublicKeyCredentials } from '../hook/useVerifyPublicKeyCredentials';
import { App } from './App';

export interface AuthenticationContainerProps {
  publicKeyOptions: SerializedPublicKeyCredentialRequestOptions;
  requestInformation: RequestInformation;
  responseUrl: string;
  t: (key: TranslationString) => string;
}

export const AuthenticationContainer: FC<AuthenticationContainerProps> = ({ t, requestInformation, publicKeyOptions, responseUrl }) => {
  const [state, dispatch] = useAppReducer(requestInformation, 'status.authentication_initial');
  const { message, errorInfo } = state;
  const [click, clicked] = useClickable();
  const verify = useVerifyPublicKeyCredentials(responseUrl);
  useAuthenticationEffect(dispatch, publicKeyOptions, verify, clicked);
  return <App message={message} errorInfo={errorInfo} requestInformation={requestInformation} t={t} onClick={click} />;
};
