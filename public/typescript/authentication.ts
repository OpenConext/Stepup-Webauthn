import { isPublicKeyCredentialType } from './functions';
import { handleApplicationEvent as hae, reload } from './gui';
import { whenResponseIsOk } from './http';
import { SerializedPublicKeyCredentialRequestOptions } from './models';
import {
  concatIfElse,
  fromSerializedPublicKeyCredentialRequestOptions,
  handleUnsupportedCredentialTypes,
  requestUserAssertion,
  retryWhenClicked,
  sendPublicKeyCredentialsToServer,
  whenWebAuthnSupported,
} from './operators';

/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialRequestOptions;

fromSerializedPublicKeyCredentialRequestOptions(hae, publicKeyOptions)
  .pipe(
    whenWebAuthnSupported(hae),
    requestUserAssertion(hae),
    concatIfElse(
      isPublicKeyCredentialType,
      sendPublicKeyCredentialsToServer(hae),
      handleUnsupportedCredentialTypes(hae),
    ),
    whenResponseIsOk(),
    reload(),
    retryWhenClicked(hae),
  ).subscribe();
