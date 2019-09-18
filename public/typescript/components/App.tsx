import React, { FC } from 'react';
import { ErrorInformation, RequestInformation, TranslationString } from '../models';
import { ErrorTable } from './ErrorTable';

export interface AppProps {
  t: (key: string) => string; requestInformation: RequestInformation;
  errorInfo: ErrorInformation | null;
  message: TranslationString;
  onClick: () => void;
}

export const App: FC<AppProps> = ({ t, message, requestInformation, errorInfo, onClick }) => {
  return (
    <div>
      <p>
        {t(message)}
      </p>
      {errorInfo && <ErrorTable t={t} errorInfo={errorInfo} clientInfo={requestInformation} />}
      {errorInfo && errorInfo.showRetry && <button onClick={onClick}>{t('retry')}</button>}
    </div>
  );
};
