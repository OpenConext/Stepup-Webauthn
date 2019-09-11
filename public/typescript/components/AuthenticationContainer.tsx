import React, { FC } from 'react';
import { useAppReducer, useAuthentication, useClickable } from '../hooks';
import { RequestInformation, SerializedPublicKeyCredentialRequestOptions } from '../models';
import { App } from './App';

/**
 * Variable from template, @see templates\default\registration.html.twig and
 * templates\default\variables.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialRequestOptions;
declare const requestInformation: RequestInformation;

export const AuthenticationContainer: FC<{ t: (key: string) => string }> = ({ t }) => {
  const [state, dispatch] = useAppReducer(requestInformation, 'status.authentication_initial');
  const [click, clicked] = useClickable();
  useAuthentication(dispatch, publicKeyOptions, clicked);
  return <App state={state} t={t} onClick={click} />;
};
