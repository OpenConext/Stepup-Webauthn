import { isPublicKeyCredentialType } from './functions';
import { handleApplicationEvent as hae, reload } from './gui';
import { whenResponseIsOk } from './http';
import { SerializedPublicKeyCredentialCreationOptions } from './models';
import {
  concatIfElse,
  fromSerializedPublicKeyCredentialCreationOptions,
  handleUnsupportedCredentialTypes,
  requestUserAttestation,
  retryWhenClicked,
  sendPublicKeyCredentialsToServer,
  whenWebAuthnSupported,
} from './operators';

/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;

fromSerializedPublicKeyCredentialCreationOptions(hae, publicKeyOptions)
  .pipe(
    whenWebAuthnSupported(),
    requestUserAttestation(hae),
    concatIfElse(
      isPublicKeyCredentialType,
      sendPublicKeyCredentialsToServer(hae),
      handleUnsupportedCredentialTypes(hae),
    ),
    whenResponseIsOk(),
    reload(),
    retryWhenClicked(hae),
  ).subscribe();
