import React, { FC } from 'react';
import { useAppReducer, useClickable, useRegistrationEffect } from '../hooks';
import { RequestInformation, SerializedPublicKeyCredentialCreationOptions } from '../models';
import { App } from './App';

/**
 * Variable from template, @see templates\default\registration.html.twig and
 * templates\default\variables.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
declare const requestInformation: RequestInformation;

export const RegistrationContainer: FC<{ t: (key: string) => string }> = ({ t }) => {
  const [state, dispatch] = useAppReducer(requestInformation, 'status.registration_initial');
  const [click, clicked] = useClickable();
  useRegistrationEffect(dispatch, publicKeyOptions, clicked);
  return <App state={state} t={t} onClick={click} />;
};
