import React, { FC } from 'react';
import { ErrorInformation, RequestInformation } from '../models';
import { MailToLink } from './MailToLink';

interface MailToLinkProps {
  t: (key: string) => string;
  errorInfo: ErrorInformation;
  clientInfo: RequestInformation;
}

export const ErrorTable: FC<MailToLinkProps> = ({ t, clientInfo, errorInfo }) => {
  const { hostname, ipAddress, requestId, sari, userAgent } = clientInfo;
  const { timestamp, code, showMailTo } = errorInfo;
  return (
    <div>
      <table className="table table-bordered">
        <tbody>
        <tr>
          <th>{t('stepup.error.timestamp')}</th>
          <td>{timestamp}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.hostname')}</th>
          <td>{hostname}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.request_id')}</th>
          <td>{requestId}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.error_code')}</th>
          <td>{code} {showMailTo && <MailToLink t={t} code={code} timestamp={timestamp} clientInfo={clientInfo} />}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.sari')}</th>
          <td>{sari}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.user_agent')}</th>
          <td>{userAgent}</td>
        </tr>
        <tr>
          <th>{t('stepup.error.ip_address')}</th>
          <td>{ipAddress}</td>
        </tr>
        </tbody>
      </table>
      <p dangerouslySetInnerHTML={{ __html: t('stepup.error.support_page.text') }} />
    </div>
  );
};
