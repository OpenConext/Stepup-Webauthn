import { AxiosInstance } from 'axios';
import { SerializedPublicKeyCredential } from './models';

export const sendPublicKeyCredentials = (client: AxiosInstance) => (credential: SerializedPublicKeyCredential) => client.post('verify-attestation-response', credential);
