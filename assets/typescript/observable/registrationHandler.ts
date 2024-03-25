import { complement } from 'ramda';
import { Observable } from 'rxjs';
import { takeWhile } from 'rxjs/operators';
import { isPublicKeyCredentialType } from '../function';
import { PublicKeyResponseValidator, whenResponseIsOk } from '../function/http';
import { ApplicationAction, FireApplicationEvent, SerializedPublicKeyCredentialCreationOptions } from '../model';
import {
  concatIfElse,
  fromSerializedPublicKeyCredentialCreationOptions,
  handleUnsupportedCredentialTypes,
  requestUserAttestation,
  whenWebAuthnSupported,
} from '../operator';
import { sendPublicKeyCredentialsToServer } from '../operator/sendPublicKeyCredentialsToServer';

export const registrationObservable: (verify: PublicKeyResponseValidator, option: SerializedPublicKeyCredentialCreationOptions, create: (options: CredentialCreationOptions) => Promise<CredentialType | null>, time: () => string) => Observable<ApplicationAction> =
  (send, option, create, time) =>
    new Observable<ApplicationAction>((subscriber) => {
      const hae: FireApplicationEvent = (type) => (value) => subscriber.next({
        type,
        value,
        timestamp: time(),
      });
      return fromSerializedPublicKeyCredentialCreationOptions(hae, option)
        .pipe(
          whenWebAuthnSupported(hae),
          requestUserAttestation(hae, create),
          concatIfElse(
            isPublicKeyCredentialType,
            sendPublicKeyCredentialsToServer(hae, send),
            handleUnsupportedCredentialTypes(hae),
          ),
          takeWhile(complement(whenResponseIsOk)),
        ).subscribe({
          complete: subscriber.complete.bind(subscriber),
          error: (error) => subscriber.error(error),
        });
    });
