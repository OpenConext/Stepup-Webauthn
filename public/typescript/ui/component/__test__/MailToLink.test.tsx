import { parseUrl } from 'query-string';
import * as React from 'react';
import TestRenderer from 'react-test-renderer';
import { MailToLink } from '../MailToLink';

it('MailToLink', () => {
  const testRenderer = TestRenderer.create((<MailToLink
    t={(value) => `[${value.replace('stepup.error.', '')}]`}
    timestamp="2019-09-11T12:37:40.987Z"
    code="12354"
    error="NotAllowedError: The operation either timed out or was not allowed."
    clientInfo={{
      supportEmail: 'email@test.nl',
      hostname: 'webauthn.test',
      ipAddress: '192.168.77.1',
      requestId: 'c91d0fd300303c3f6caf839222082b6a',
      sari: '__234343',
      userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
    }}
  />));

  const result: any = testRenderer.toJSON();
  expect(result.type).toBe('a');
  const parsed = parseUrl(result.props.href);
  expect(parsed.url).toBe('mailto:email@test.nl');
  expect(parsed.query.body).toEqual(`[support_page.mail_intro]

[timestamp]: 2019-09-11T12:37:40.987Z
[hostname]: webauthn.test
[request_id]: c91d0fd300303c3f6caf839222082b6a
[sari]: __234343
[error_code]: 12354
[user_agent]: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36
[ip_address]: 192.168.77.1

NotAllowedError: The operation either timed out or was not allowed.

[support_page.mail_closure]

`);
  expect(parsed.query.subject).toEqual('[support_page.mail_subject] 12354');
});
