import 'core-js';
import * as React from 'react';
import ReactDom from 'react-dom';
import { translate } from './function';
import { RequestInformation, SerializedPublicKeyCredentialRequestOptions } from './model';
import { AuthenticationContainer } from './ui/component/AuthenticationContainer';

/**
 * Variable from template, @see templates\default\registration.html.twig and
 * templates\default\variables.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialRequestOptions;
declare const requestInformation: RequestInformation;
declare const responseUrl: string;

const startButtonElement = document.getElementById('startbutton');
startButtonElement?.addEventListener('click', () => {
  ReactDom.render(
    <AuthenticationContainer
      requestInformation={requestInformation}
      publicKeyOptions={publicKeyOptions}
      responseUrl={responseUrl}
      t={translate}
    />,
    document.getElementById('root'),
  );
});
