import { complement } from 'ramda';
import { from } from 'rxjs';
import { concatMap, filter } from 'rxjs/operators';
import { isPublicKeyCredentialType } from './functions';

export const requestUserAttestation = concatMap((publicKey: PublicKeyCredentialCreationOptions) => from(navigator.credentials.create({ publicKey })));

export const requestUserAssertion = concatMap((publicKey: PublicKeyCredentialCreationOptions) => from(navigator.credentials.get({ publicKey })));

export const filterPublicKeyCredentialType = filter(isPublicKeyCredentialType);

export const excludePublicKeyCredentialType = filter(complement(isPublicKeyCredentialType));
