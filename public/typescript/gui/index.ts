import { fromEvent, Observable } from 'rxjs';
import { take, tap } from 'rxjs/operators';

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
