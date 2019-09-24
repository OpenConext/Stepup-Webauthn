import { useReducer, useState } from 'react';
import { RequestInformation } from '../../model';
import { appReducer } from '../../reducer/appReducer';
import { loggingAppReducerDecorator } from '../../reducer/loggingAppReducerDecorator';

export const useAppReducer = (requestInformation: RequestInformation, initialMessage: string) => useReducer(
  loggingAppReducerDecorator(appReducer),
  useState({
    message: initialMessage,
    errorInfo: null,
    requestInformation,
  })[0]);
