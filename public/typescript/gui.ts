import { fromEvent, Observable } from 'rxjs';
import { take, tap } from 'rxjs/operators';
import { RegistrationState } from './models';

export const retryClicked = () => new Observable((subscriber) => {
  const retryButton = document.getElementById('retryButton');
  if (!(retryButton instanceof HTMLButtonElement)) {
    throw new Error('Could not found "retryButton" dom element');
  }
  retryButton.classList.remove('hidden');
  fromEvent(retryButton, 'click')
    .pipe(take(1))
    .subscribe(subscriber);
  return () => retryButton.classList.add('hidden');
});

export const submitForm = () => tap(() => {
  const element = document.getElementById('register');
  if (!(element instanceof HTMLButtonElement)) {
    throw new Error('Could not found "register" dom element');
  }
  element.click();
});

export const updateState = (type: RegistrationState) => tap<any>((value) => {
  // tslint:disable-next-line:no-console
  console.log(RegistrationState[type], value);
  const element = document.getElementById('status');
  if (!(element instanceof HTMLDivElement)) {
    throw new Error('Could not found "status" dom element');
  }
  element.innerText = `${RegistrationState[type]}: ${JSON.stringify(value)}`;
});
