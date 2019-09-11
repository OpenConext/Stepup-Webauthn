import React, { FC } from 'react';
import { ApplicationState } from '../models';
import { ErrorTable } from './ErrorTable';

export const App: FC<{ t: (key: string) => string, state: ApplicationState, onClick: () => void }> = ({ t, state, onClick }) => {
  const { message, requestInformation, errorInfo } = state;
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
