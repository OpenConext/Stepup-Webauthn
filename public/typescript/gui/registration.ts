import { tap } from 'rxjs/operators';
import { decode } from 'urlsafe-base64';
import { RegistrationState as S, SerializedPublicKeyCredential } from '../models';
import { handleServerResponse, showGeneralErrorStatus, showInitialStatus } from './index';

export const updateState = (type: S) => tap<any>((value) => {
  // tslint:disable-next-line:no-console
  console.log(S[type], value);

  switch (type) {
    case S.REQUEST_USER_FOR_ATTESTATION:
      showInitialStatus();
      break;
    case S.PUBLIC_KEY_CREDENTIALS_SERIALIZED:
      const credentials: SerializedPublicKeyCredential = value;
      // tslint:disable-next-line:no-console
      console.log('clientDataJSON', decode(credentials.response.clientDataJSON).toString());
      break;
    case S.ERROR:
      if (value.response) {
        handleServerResponse(value.response.data.status);
        break;
      }
      showGeneralErrorStatus();
      break;
  }
});
