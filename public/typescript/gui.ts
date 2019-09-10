import { AxiosResponse } from 'axios';
import { bind, empty } from 'ramda';
import { fromEvent, Observable } from 'rxjs';
import { take, tap } from 'rxjs/operators';
import { decode } from 'urlsafe-base64';
import { assertElement, extractedTableValues, getStringAttribute } from './domFunctions';
import { createErrorCode } from './functions';
import { ApplicationEvent, SerializedPublicKeyCredential } from './models';
import { FireApplicationEvent } from './operators';

export const handleApplicationEvent: FireApplicationEvent = (type: ApplicationEvent) => tap((value: any) => {
  log(ApplicationEvent[type], value);

  switch (type) {
    case ApplicationEvent.NOT_SUPPORTED:
      showWebAuthnNotSupportedStatus();
      setErrorCode(createErrorCode(ApplicationEvent[type]));
      break;

    case ApplicationEvent.REQUEST_USER_FOR_ATTESTATION:
      showInitialStatus();
      break;
    case ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value;
      log('clientDataJSON', decode(credentials.response.clientDataJSON).toString());
      break;
    case ApplicationEvent.REQUEST_USER_FOR_ASSERTION:
      showInitialStatus();
      break;
    case ApplicationEvent.ERROR:
      if (value.response) {
        handleServerResponse(value.response);
        break;
      }
      showGeneralErrorStatus();
      setErrorCode(createErrorCode(value.toString()));
      setErrorMailtoLink(value);
      break;
  }
}) as any;

/**
 * {@see \App\ValidationJsonResponse} for all server response types
 */
export const handleServerResponse = (response: AxiosResponse) => {
  let status: string | undefined = response.data.status;
  if (!status) {
    // Last resort, something when't completely wrong, show server response page.
    const contentType: string = response.headers['content-type'];
    if (contentType.indexOf('text/html;') >= 0) {
      document.body.innerHTML = response.data;
      return;
    }
    status = 'error';
  }
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
  if (response.data.error_code) {
    setErrorCode(response.data.error_code);
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
 * Class name can be found in authentication, registration and general status templateApplicationEvent.
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
  a.target = '_black';
  return a;
};

/**
 * Extract error information of the error table defined in 'general_statuApplicationEvent.twig'.
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
