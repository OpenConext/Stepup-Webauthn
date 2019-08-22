import { tap } from 'rxjs/operators';
import { AuthenticationState as S } from '../models';
import { handleServerResponse, showGeneralErrorStatus, showInitialStatus } from './index';

export const updateState = (type: S) => tap<any>((value) => {
  // tslint:disable-next-line:no-console
  console.log(S[type], value);

  switch (type) {
    case S.REQUEST_USER_FOR_ASSERTION:
      showInitialStatus();
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
