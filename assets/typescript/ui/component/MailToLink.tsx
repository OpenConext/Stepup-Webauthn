import React, { FC } from 'react';
import { RequestInformation } from '../../model';

interface MailToLinkProps {
  t: (key: string) => string;
  code: string;
  error: string;
  timestamp: string;
  clientInfo: RequestInformation;
}

export const MailToLink: FC<MailToLinkProps> = ({ t, clientInfo, code, error, timestamp }) => {
  const { hostname, ipAddress, requestId, sari, userAgent, supportEmail } = clientInfo;
  const subject = `${t('stepup.error.support_page.mail_subject')} ${code}`;
  const body = `${t('stepup.error.support_page.mail_intro')}

${t('stepup.error.timestamp')}: ${timestamp}
${t('stepup.error.hostname')}: ${hostname}
${t('stepup.error.request_id')}: ${requestId}
${t('stepup.error.sari')}: ${sari}
${t('stepup.error.error_code')}: ${code}
${t('stepup.error.user_agent')}: ${userAgent}
${t('stepup.error.ip_address')}: ${ipAddress}

${error}

${t('stepup.error.support_page.mail_closure')}

`;
  const href = `mailto:${supportEmail}?subject=${encodeURI(subject)}&body=${encodeURI(body)}`;
  return (
    <a href={href}>{t('stepup.error.support_page.mail_to')}</a>
  );
};
