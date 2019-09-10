import { bind, empty } from 'ramda';
import { fromEvent, Observable } from 'rxjs';
import { take, tap } from 'rxjs/operators';
import { decode } from 'urlsafe-base64';
import { assertElement, extractedTableValues, getStringAttribute } from './domFunctions';
import { createArtCode } from './functions';
import { ApplicationEvent as S, SerializedPublicKeyCredential } from './models';
import { FireApplicationEvent } from './operators';

export const handleApplicationEvent: FireApplicationEvent = (type: S) => tap((value: any) => {
  log(S[type], value);

  switch (type) {
    case S.NOT_SUPPORTED:
      showWebAuthnNotSupportedStatus();
      setErrorCode(createArtCode(S[type]));
      break;

    case S.REQUEST_USER_FOR_ATTESTATION:
      showInitialStatus();
      break;
    case S.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value;
      log('clientDataJSON', decode(credentials.response.clientDataJSON).toString());
      break;
    case S.REQUEST_USER_FOR_ASSERTION:
      showInitialStatus();
      break;
    case S.ERROR:
      if (value.response) {
        handleServerResponse(value.response.data.status, value.response.data.error_code);
        break;
      }
      showGeneralErrorStatus();
      setErrorCode(createArtCode(value.toString()));
      setErrorMailtoLink(value);
      break;
  }
}) as any;

/**
 * {@see \App\ValidationJsonResponse} for all server response types
 */
export const handleServerResponse = (status: string, error_code?: string) => {
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
  if (error_code) {
    setErrorCode(error_code);
  }
};

// tslint:disable-next-line:no-console
const log: (...args: any[]) => void = typeof console !== 'undefined' ? bind(console.info, console) : empty;

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

/**
 * Show the error table
 *  - Set the error code.
 *  - Set timestamp.
 */
function setErrorCode(errorCode: string) {
  getErrorTableElement().classList.remove('hidden');
  getErrorCodeElement().innerText = `${errorCode} `;
  getErrorTimestampElement().innerText = (new Date()).toISOString();
}

/**
 * Add to error code an mail to link
 * @param error
 */
function setErrorMailtoLink(error: unknown) {
  const information = getErrorInformation(getErrorTableElement());
  getErrorCodeElement().appendChild(createMailTo(error, information));
}

/**
 * Create mail to link from error information.
 */
export const createMailTo = (error: unknown, { url, values, closure, intro, linkText, errorCode, subjectIntro }: ReturnType<typeof getErrorInformation>) => {
  let body = `${intro}\n\n`;
  for (const [name, value] of Array.from(values.entries())) {
    body += `${name}: ${value}\n`;
  }
  body += `\n${error}\n\n${closure}\n`;
  const a = document.createElement('a');
  const subject = `${subjectIntro} ${errorCode}`;
  a.href = `mailto:${url}?subject=${encodeURI(subject)}&body=${encodeURI(body)}`;
  a.innerText = linkText;
  return a;
};

/**
 * Extract error information of the error table defined in 'general_status.twig'.
 */
export const getErrorInformation = (table: HTMLTableElement) => {
  const errorCode = assertElement(table.getElementsByClassName('error_code')[0]);
  const attribute = getStringAttribute(errorCode);
  return {
    url: attribute('data-email'),
    linkText: attribute('data-email-link-text'),
    subjectIntro: attribute('data-email-subject'),
    intro: attribute('data-email-intro'),
    closure: attribute('data-email-closure'),
    values: extractedTableValues(table),
    errorCode: errorCode.innerHTML,
  };
};

export const getErrorTableElement = (): HTMLTableElement => assertElement(document.getElementById('error_table')) as any;
export const getErrorCodeElement = () => assertElement(document.getElementById('error_code'));
export const getErrorTimestampElement = () => assertElement(document.getElementById('error_timestamp'));

export const showInitialStatus = () => showStatus('initial');
export const showGeneralErrorStatus = () => showStatus('general_error');
export const showNoActiveRequestStatus = () => showStatus('no_active_request');
export const showMissingAttestationStatementStatus = () => showStatus('missing_attestation_statement');
export const showAuthenticatorNotSupportedStatus = () => showStatus('authenticator_not_supported');
export const showWebAuthnNotSupportedStatus = () => showStatus('webauthn_not_supported');

export const reload = () => tap(() => {
  window.location.reload();
});
