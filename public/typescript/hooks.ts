import { curry } from 'ramda';
import { useCallback, useEffect, useReducer, useState } from 'react';
import { Observable, Subject } from 'rxjs';
import { appReducer } from './appReducer';
import { authenticationHandler } from './authenticationHandler';
import { loggingAppReducerDecorator } from './functions';
import {
  Action,
  ApplicationEvent,
  RequestInformation,
  SerializedPublicKeyCredentialCreationOptions,
  SerializedPublicKeyCredentialRequestOptions,
} from './models';
import { registrationHandler } from './registrationHandler';

export const useAppReducer = (requestInformation: RequestInformation, initialMessage: string) => useReducer(
  loggingAppReducerDecorator(appReducer),
  {
    message: initialMessage,
    errorInfo: null,
    requestInformation,
  });

export const useAuthentication = (dispatch: (action: Action) => void, publicKeyOptions: SerializedPublicKeyCredentialRequestOptions, whenClicked: Observable<unknown>) =>
  useEffect(
    () => {
      authenticationHandler(
        curry((type: ApplicationEvent, value: unknown) => {
          dispatch({ value, type, timestamp: (new Date()).toISOString() });
        }),
        publicKeyOptions,
        whenClicked,
      );
    },
    [],
  );

export const useRegistrationEffect = (dispatch: (action: Action) => void, publicKeyOptions: SerializedPublicKeyCredentialCreationOptions, whenClicked: Observable<unknown>) =>
  useEffect(
    () => {
      registrationHandler(
        curry((type: ApplicationEvent, value: unknown) => {
          dispatch({ value, type, timestamp: (new Date()).toISOString() });
        }),
        publicKeyOptions,
        whenClicked,
      );
    },
    [],
  );

export const useClickable: () => [() => void, Observable<unknown>] = () => {
  const [whenClicked] = useState(new Subject());
  const click = useCallback(() => whenClicked.next(), []);
  return [click, whenClicked];
};
