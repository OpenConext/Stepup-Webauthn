import { Observable } from 'rxjs';
import { isPublicKeyCredentialType } from './functions';
import { whenResponseIsOk } from './http';
import { ApplicationEvent, SerializedPublicKeyCredentialRequestOptions } from './models';
import {
  concatIfElse,
  FireApplicationEvent,
  fromSerializedPublicKeyCredentialRequestOptions,
  handleUnsupportedCredentialTypes,
  reload,
  requestUserAssertion,
  retryWhenClicked,
  sendPublicKeyCredentialsToServer,
  whenWebAuthnSupported,
} from './operators';

export const authenticationHandler: (hae: FireApplicationEvent, option: SerializedPublicKeyCredentialRequestOptions, clicked: Observable<unknown>) => void = (hae, options, clicked) =>
  fromSerializedPublicKeyCredentialRequestOptions(hae, options)
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
      retryWhenClicked(hae, clicked),
    ).subscribe({ error: hae(ApplicationEvent.ERROR) });
