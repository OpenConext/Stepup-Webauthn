import { SerializedPublicKeyCredential } from '../model';
import { ServerResponse } from '../model/response';

export type PublicKeyResponseValidator = (credential: SerializedPublicKeyCredential) => Promise<ServerResponse>;

export const whenResponseIsOk = (response: any | ServerResponse): boolean => {
  if (!(response instanceof Object)) {
    return false;
  }
  if (typeof response.data !== 'object') {
    return false;
  }
  return response.data.status === 'ok';
};
