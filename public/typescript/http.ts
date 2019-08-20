import { AxiosInstance } from 'axios';
import { filter } from 'rxjs/operators';
import { SerializedPublicKeyCredential } from './models';

declare var responseUrl: string;

export const verifyPublicKeyCredentials = (client: AxiosInstance) =>
  (credential: SerializedPublicKeyCredential) =>
    client.post(responseUrl, credential);

export const whenResponseIsOk = () => filter((response: any) => {
  return response.data.status === 'ok';
});
