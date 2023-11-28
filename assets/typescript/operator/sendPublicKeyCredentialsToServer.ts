import { Observable } from 'rxjs';
import { concatMap, filter, map, tap } from 'rxjs/operators';
import { isPublicKeyCredentialType } from '../function';
import { PublicKeyResponseValidator } from '../function/http';
import { ApplicationEvent, FireApplicationEvent, SerializedPublicKeyCredential } from '../model';
import { serializePublicKeyCredential } from '../serialisation';

export const sendPublicKeyCredentialsToServer = (fe: FireApplicationEvent, send: PublicKeyResponseValidator) => (s: Observable<CredentialType | null>): Observable<unknown> => s.pipe(
  filter(isPublicKeyCredentialType),
  tap<PublicKeyCredential>(fe(ApplicationEvent.SERIALIZE_PUBLIC_KEY_CREDENTIALS)),
  // @ts-ignore
  map(serializePublicKeyCredential),
  tap<SerializedPublicKeyCredential>(fe(ApplicationEvent.PUBLIC_KEY_CREDENTIALS_SERIALIZED)),
  tap<SerializedPublicKeyCredential>(fe(ApplicationEvent.SENDING_PUBLIC_KEY_CREDENTIALS)),
  concatMap(send),
  tap(fe(ApplicationEvent.RECEIVED_SERVER_RESPONSE)),
);
