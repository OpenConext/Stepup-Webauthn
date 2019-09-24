import { useCallback } from 'react';
import { ajax } from 'rxjs/ajax';
import { PublicKeyResponseValidator } from '../../function/http';
import { SerializedPublicKeyCredential } from '../../model';

export const useVerifyPublicKeyCredentials = (url: string): PublicKeyResponseValidator =>
  useCallback<PublicKeyResponseValidator>((credentials: SerializedPublicKeyCredential) => ajax.post(url, JSON.stringify(credentials), { 'Content-Type': 'application/json' }) as any, [url]);
