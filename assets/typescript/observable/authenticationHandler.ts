import { complement } from 'ramda';
import { Observable } from 'rxjs';
import { takeWhile } from 'rxjs/operators';
import { isPublicKeyCredentialType } from '../function';
import { PublicKeyResponseValidator, whenResponseIsOk } from '../function/http';
import {
  ApplicationAction,
  ApplicationEvent,
  FireApplicationEvent,
  SerializedPublicKeyCredentialRequestOptions,
} from '../model';
import {
  concatIfElse,
  fromSerializedPublicKeyCredentialRequestOptions,
  handleUnsupportedCredentialTypes,
  requestUserAssertion,
  whenWebAuthnSupported,
} from '../operator';
import { sendPublicKeyCredentialsToServer } from '../operator/sendPublicKeyCredentialsToServer';

/**
 * When authentication is finished, the stream completes.
 */
export const authenticationObservable: (
  send: PublicKeyResponseValidator,
  option: SerializedPublicKeyCredentialRequestOptions,
  get: (options?: CredentialRequestOptions) => Promise<CredentialType | null>,
  time: () => string) => Observable<ApplicationAction> =
  (send, options, get, time) =>
    new Observable<ApplicationAction>((subscriber) => {
      const hae: FireApplicationEvent = (type: ApplicationEvent) => (value: any) => subscriber.next({
        type,
        value,
        timestamp: time(),
      });
      return fromSerializedPublicKeyCredentialRequestOptions(hae, options)
        .pipe(
          whenWebAuthnSupported(hae),
          requestUserAssertion(hae, get),
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
