import { AxiosInstance } from 'axios';
import { filter } from 'rxjs/operators';
import { SerializedPublicKeyCredential } from './models';

/**
 * Variable from template, @see templates\default\registration.html.twig or templates\default\authentication.html.twig
 */
declare var responseUrl: string;

export const verifyPublicKeyCredentials = (client: AxiosInstance) =>
  (credential: SerializedPublicKeyCredential) =>
    client.post(responseUrl, credential);

export const whenResponseIsOk = () => filter((response: any) => {
  return response.data.status === 'ok';
});
