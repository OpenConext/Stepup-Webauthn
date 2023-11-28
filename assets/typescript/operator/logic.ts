import { merge, MonoTypeOperatorFunction, Observable, of } from 'rxjs';
import { concatMap, mergeMap, retryWhen, tap } from 'rxjs/operators';
import { ApplicationAction, ApplicationEvent, FireApplicationEvent } from '../model';

export const concatIfElse = <T, R1, R2>(
  condition: (value: T) => boolean,
  whenTrue: (obs: Observable<T>) => Observable<R1>,
  whenFalse: (obs: Observable<T>) => Observable<R2>,
) => concatMap((value: T) => condition(value) ? whenTrue(of(value)) : whenFalse(of(value)));

export const retryWith = (fe: FireApplicationEvent, trigger: Observable<unknown>): MonoTypeOperatorFunction<ApplicationAction> =>
  retryWhen((errors) => merge(errors.pipe(
    tap(fe(ApplicationEvent.ERROR)),
    mergeMap(() => trigger),
  )));
