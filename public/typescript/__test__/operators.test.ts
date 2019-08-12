import { of } from 'rxjs';
import { toArray } from 'rxjs/operators';
import { excludePublicKeyCredentialType, filterPublicKeyCredentialType, requestUserAttestation } from '../operators';

it('requestUserAttestation', async () => {

  const publicKeyOption: PublicKeyCredentialCreationOptions = { foo: 'my key option stub' } as any;

  (navigator as any).credentials = {} as any;
  navigator.credentials.create = jest.fn().mockResolvedValue('success');

  await expect(requestUserAttestation(of(publicKeyOption)).toPromise()).resolves.toEqual('success');

  expect(navigator.credentials.create).toBeCalledWith({ publicKey: publicKeyOption });
});

it('filterPublicKeyCredentialType', async () => {
  // tslint:disable-next-line:prefer-array-literal
  const types: Array<CredentialType | null> = [
    null,
    {
      type: 'password-key',
    },
    {
      type: 'public-key',
    },
  ] as any;

  await expect(of(...types).pipe(filterPublicKeyCredentialType).pipe(toArray()).toPromise()).resolves.toEqual([
    {
      type: 'public-key',
    },
  ]);
});

it('excludePublicKeyCredentialType', async () => {
  // tslint:disable-next-line:prefer-array-literal
  const types: Array<CredentialType | null> = [
    null,
    {
      type: 'password-key',
    },
    {
      type: 'public-key',
    },
  ] as any;

  await expect(of(...types).pipe(excludePublicKeyCredentialType).pipe(toArray()).toPromise()).resolves.toEqual([
    null,
    {
      type: 'password-key',
    },
  ]);
});
