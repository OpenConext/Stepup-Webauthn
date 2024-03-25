import { useCallback, useState } from 'react';
import { Observable, Subject } from 'rxjs';

export * from './useAppReducer';
export * from './useAuthenticationEffect';
export * from './useRegistrationEffect';

export const useClickable: () => [() => void, Observable<unknown>] = () => {
  const [whenClicked] = useState(new Subject());
  const click = useCallback(() => whenClicked.next(), []);
  return [click, whenClicked];
};
