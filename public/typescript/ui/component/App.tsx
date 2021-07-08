import React, { FC } from 'react';
import { ErrorInformation, RequestInformation, TranslationString } from '../../model';
import { ErrorTable } from './ErrorTable';

export interface AppProps {
  t: (key: string) => string; requestInformation: RequestInformation;
  started: boolean;
  startMessage: TranslationString;
  errorInfo: ErrorInformation | null;
  message: TranslationString;
  onClick: () => void;
  onStart: () => void;
}

export const App: FC<AppProps> = ({ t, started, startMessage, message, requestInformation, errorInfo, onClick, onStart }) => {
  return (
    <div>
      <p>
        {t(message)}
      </p>
      {started && errorInfo && <ErrorTable t={t} errorInfo={errorInfo} clientInfo={requestInformation} />}
      {started && errorInfo && errorInfo.showRetry && <button className="btn btn-primary" onClick={onClick}>{t('retry')}</button>}
      {!started && <button className="btn btn-primary" onClick={onStart}>{t(startMessage)}</button>}
    </div>
  );
};
