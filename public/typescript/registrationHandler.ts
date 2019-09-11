import { Observable } from 'rxjs';
import { isPublicKeyCredentialType } from './functions';
import { whenResponseIsOk } from './http';
import { ApplicationEvent, SerializedPublicKeyCredentialCreationOptions } from './models';
import {
  concatIfElse,
  FireApplicationEvent,
  fromSerializedPublicKeyCredentialCreationOptions,
  handleUnsupportedCredentialTypes,
  reload,
  requestUserAttestation,
  retryWhenClicked,
  sendPublicKeyCredentialsToServer,
  whenWebAuthnSupported,
} from './operators';

export const registrationHandler: (hae: FireApplicationEvent, option: SerializedPublicKeyCredentialCreationOptions, clicked: Observable<unknown>) => void = (hae, option, clicked) =>
  fromSerializedPublicKeyCredentialCreationOptions(hae, option)
    .pipe(
      whenWebAuthnSupported(hae),
      requestUserAttestation(hae),
      concatIfElse(
        isPublicKeyCredentialType,
        sendPublicKeyCredentialsToServer(hae),
        handleUnsupportedCredentialTypes(hae),
      ),
      whenResponseIsOk(),
      reload(),
      retryWhenClicked(hae, clicked),
    ).subscribe({ error: hae(ApplicationEvent.ERROR) });
