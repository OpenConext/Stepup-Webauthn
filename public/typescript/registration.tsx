import 'core-js';
import * as React from 'react';
import ReactDom from 'react-dom';
import { translate } from './function';
import { RequestInformation, SerializedPublicKeyCredentialCreationOptions } from './model';
import { RegistrationContainer } from './ui/component/RegistrationContainer';

/**
 * Variable from template, @see templates\default\registration.html.twig and
 * templates\default\variables.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
declare const requestInformation: RequestInformation;
declare const responseUrl: string;

ReactDom.render(
  <RegistrationContainer
    t={translate}
    responseUrl={responseUrl}
    publicKeyOptions={publicKeyOptions}
    requestInformation={requestInformation}
  />,
  document.getElementById('root'),
);
