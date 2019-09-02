import { bind, empty } from 'ramda';
import { fromEvent, Observable } from 'rxjs';
import { take, tap } from 'rxjs/operators';
import { decode } from 'urlsafe-base64';
import { ApplicationEvent as S, SerializedPublicKeyCredential } from './models';
import { FireApplicationEvent } from './operators';

export const handleApplicationEvent: FireApplicationEvent = (type: S) => tap((value: any) => {
  // tslint:disable-next-line:no-console
  console.log(S[type], value);

  switch (type) {
    case S.REQUEST_USER_FOR_ATTESTATION:
      showInitialStatus();
      break;
    case S.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value;
      // tslint:disable-next-line:no-console
      console.log('clientDataJSON', decode(credentials.response.clientDataJSON).toString());
      break;
    case S.REQUEST_USER_FOR_ASSERTION:
      showInitialStatus();
      break;
    case S.ERROR:
      if (value.response) {
        handleServerResponse(value.response.data.status);
        break;
      }
      showGeneralErrorStatus();
      break;
  }
}) as any;

/**
 * {@see \App\ValidationJsonResponse} for all server response types
 */
export const handleServerResponse = (status: string) => {
  switch (status) {
    case 'deviceNotSupported':
      showAuthenticatorNotSupportedStatus();
      break;
    case 'noAuthenticationRequired':
      showNoActiveRequestStatus();
      break;
    case 'noRegistrationRequired':
      showNoActiveRequestStatus();
      break;
    case 'missingAttestationStatement':
      showMissingAttestationStatementStatus();
      break;
    case 'error':
      showGeneralErrorStatus();
      break;
    case 'invalid':
      showGeneralErrorStatus();
      break;
  }
};

// tslint:disable-next-line:no-console
export const log: (...args: any[]) => void = typeof console !== 'undefined' ? bind(console.info, console) : empty;

export const retryClicked = () => new Observable((subscriber) => {
  const retryButton = document.getElementById('retry_button');
  if (!(retryButton instanceof HTMLButtonElement)) {
    throw new Error('Could not found "retry_button" dom element');
  }
  retryButton.classList.remove('hidden');
  fromEvent(retryButton, 'click')
    .pipe(take(1))
    .subscribe(subscriber);
  return () => retryButton.classList.add('hidden');
});

/**
 * Class name can be found in authentication, registration and general status templates.
 */
function showStatus(name: string) {
  const elements: HTMLCollectionOf<HTMLDivElement> = document.getElementsByClassName('status') as any;
  for (const elementsKey of Array.from(elements)) {
    if (elementsKey.classList.contains(name)) {
      elementsKey.classList.remove('hidden');
    } else if (!elementsKey.classList.contains('hidden')) {
      elementsKey.classList.add('hidden');
    }
  }
}

export const showInitialStatus = () => showStatus('initial');
export const showGeneralErrorStatus = () => showStatus('general_error');
export const showNoActiveRequestStatus = () => showStatus('no_active_request');
export const showMissingAttestationStatementStatus = () => showStatus('missing_attestation_statement');
export const showAuthenticatorNotSupportedStatus = () => showStatus('authenticator_not_supported');
export const showWebAuthnNotSupportedStatus = () => showStatus('webauthn_not_supported');

export const reload = () => tap(() => {
  window.location.reload();
});
