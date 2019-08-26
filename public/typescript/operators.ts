import { complement } from 'ramda';
import { from, Observable, of } from 'rxjs';
import { concatMap, filter } from 'rxjs/operators';
import { isPublicKeyCredentialType } from './functions';

export const requestUserAttestation = concatMap((publicKey: PublicKeyCredentialCreationOptions) => from(navigator.credentials.create({ publicKey })));

export const requestUserAssertion = concatMap((publicKey: PublicKeyCredentialCreationOptions) => from(navigator.credentials.get({ publicKey })));

export const filterPublicKeyCredentialType = filter(isPublicKeyCredentialType);

export const excludePublicKeyCredentialType = filter(complement(isPublicKeyCredentialType));

export const concatIfElse = <T, R1, R2>(
  condition: (value: T) => boolean,
  whenTrue: (obs: Observable<T>) => Observable<R1>,
  whenFalse: (obs: Observable<T>) => Observable<R2>,
) => concatMap((value: T) => condition(value) ? whenTrue(of(value)) : whenFalse(of(value)));
